#!/usr/bin/php
<?php
/* VERY rough draft 
    just the logic for listening and installing bundles, will be implemented into 
    1 bundler script w all functions eventually

    the installer functions will only run on the qa and prod clusters. it listens
    for incoming messages from deployment to install bundles and also return status
    of the bundle back to deployment
*/
require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/get_host_info.inc';

/* function getBundlePath(string $bundle_name){
    //need to alter paths to whatever we end up doing for each vm <<<------
    //DONT FORGET 
    $BUNDLE_PATHS = [
        //frontend bundles
        'userFeatures' => '/var/www/html/',
        'clubFeatures' => '/var/www/html/',
        'bookFeatures' => '/var/www/html/',
        'baseFeatures' => '/var/www/html/', 

        //backend bundles
        'userData' => '/home/backend/sql_db',
        'bookData' => '/home/backend/api_db',
        'databaseProcess' => '/home/backend/rabbitMQ',
        'databaseDaemon' => '/home/backend/daemon',

        //dmz bundles
        'apiProcess' => '/home/api/',
        'apiDaemon' => '/home/api/daemon',
        'apiCron' => '/home/api/cron',
    ];

    return $BUNDLE_PATHS[$bundle_name];
}
not sure if needed anymore with the config scripts

*/


/* WHAT GETS SENT TO THE INSTALL LISTENER FROM DEPLOY SENDBUNDLE():
  $request = [
    'type' => 'install_bundle',
    'path' => $deployInfo['path'],
    'bundle_name' => $deployInfo['bundle_name'],
    'version' => $deployInfo['version'],
    'vm_ip' => $deployInfo['vm_ip']
  ];

*/

function sendStatus(string $bundle_name, int $version, string $status, string $cluster)
{
    try {
        $client = new rabbitMQClient(__DIR__ . '/host.ini', 'deployStatus');

        $status_map = [
            'type' => 'status_update',
            'bundle_name' => $bundle_name,
            'version' => $version,
            'status' => $status, // passed or failed
            'cluster' => $cluster //qa or prod
        ];

        $response = $client->send_request($status_map);
        echo "status sent to deployment:\n";
        var_dump($response);
    } catch (Exception $e) {
        echo "failed to send status to deployment: " . $e->getMessage;
    }
}

function installBundle(array $req)
{
    $bundle_name = $req['bundle_name'] ?? '';
    $version = $req['version'] ?? '';
    $tar = $req['tar_name'] ?? ($req['path'] ?? '');
    $cluster = $req['cluster'] ?? ''; // temporary until we get deploy script to send the cluster
    $cluster_user = strtolower("aarc-$cluster"); // to know current cluster users
    //$vm_ip = $req['vm_ip'] ?? trim(shell_exec("hostname -I | awk '{print $1}'"));


    if (!$bundle_name || !$version || !$tar) {
        return ['status' => 'fail', 'message' => 'missing install requirements'];
    }

    $bundleDir = "/var/www/bundles";
    $bundleFile = $bundleDir . "/" . $tar;

    if (!file_exists($bundleFile)) { // bundle needs to exist to be installed
        echo "bundle not found: $bundleFile\n";
        return ['status' => 'fail', 'message' => 'bundle missing'];
    }

    /*
    https://www.php.net/manual/en/function.mkdir.php
    https://www.php.net/manual/en/function.uniqid.php
    https://systemd.io/TEMPORARY_DIRECTORIES/
    https://linuxvox.com/blog/linux-tmp-folder/
    https://linuxvox.com/blog/linux-install-from-tar-gz/#google_vignette
    https://www.php.net/manual/en/function.escapeshellarg.php
    */

    // making a temporary directory and setting permissions
    $tmp = "/tmp/deployment_extract" . uniqid();
    mkdir($tmp, 0755, true);

    // extract bundle/tar
    $cmd = "tar -xzf " . escapeshellarg($bundleFile) . " -C " . escapeshellarg($tmp);
    exec($cmd, $output, $result);

    if ($result !== 0) {
        echo "bundle extraction failed\n";
        sendStatus($bundle_name, $version, "failed", $cluster);
        return ['status' => 'fail', 'message' => 'tar extraction failed'];
    }

    // rea's edit --> check the vm_ip or current cluster
    // the goal is to update the ip address and/or daemon filepath before configure.sh is ran, i think
    $cluster_rmq = null;
    switch ($cluster) { // assign
        case "QA":
            $cluster_rmq = "172.29.219.213";
            break;
        case "Prod":
            $cluster_rmq = "172.30.219.213"; // WILL NEED TO CHANGE WHEN PROD LAYER IS BUILT
            break;
    }

    /* example of the .service scripts located int the daemon folder : [Unit]
Description=Backround process for fetching data from library API
After=network.target
StartLimitIntervalSec=0

[Service]
Type=simple
ExecStart=/usr/bin/php /home/rea-sihombing/Project/IT490-AARC/api/Library_API.php

User=rea-sihombing

Restart=always
RestartSec=1

[Install]
WantedBy=multi-user.target */

    switch ($bundle_name) {
        case "frontendProcess":
            shell_exec("sed -i 's/\b172.28.219.213\b/$cluster_rmq/g' $tmp/rabbitMQ/host.ini");
            break;
        case "backendProcess":
            shell_exec("sed -i 's/\b172.28.219.213\b/$cluster_rmq/g' $tmp/backend/rabbitMQ/host.ini");
            // UPDATE DAEMON
            shell_exec("sed -i 's/rea-sihombing/Project/IT490-AARC\b/$cluster_user/g' $tmp/api/daemon/rabbitMQ/host.ini");
            // [WIP] UPDATE CRON FILEPATH TOO !!
            break;
        case "apiProcess":
            shell_exec("sed -i 's/172.28.219.213/$cluster_rmq/g' $tmp/api/rmqAccess.ini");
            // UPDATE DAEMON
            shell_exec("sed -i 's|rea-sihombing/Project/IT490-AARC/||g' $tmp/api/daemon/libraryapi.service");// --> changes ExecStart filepath
            shell_exec("sed -i 's|rea-sihombing|$cluster_user|g' $tmp/api/daemon/libraryapi.service"); // --> changes User name
            break;
    }
    // sed delimiters : https://stackoverflow.com/questions/5864146/using-different-delimiters-in-sed-commands-and-range-addresses

    // NOTE: var/www/bundles NEEDS TO BE OWNED BY ITS USER (aarc-qa or aarc-prod)
    echo "Running configure.sh script...\n";
    exec("cd $tmp ; ./configure.sh", $configOutput, $configResultCode);
    if ($configResultCode !== 0) {
        echo "bundle configure installation failed\n";
        sendStatus($bundle_name, $version, "failed", $cluster);
        return ['status' => 'fail', 'message' => 'configure script failed'];
    }
    echo "Successful configure.sh install\n";
    sendStatus($bundle_name, $version, "passed", $cluster);
    return ['status' => 'success', 'message' => 'Bundle installed'];


    // end of Rea's Draft


    /*
    // AIDA's
    //configure bundle/tar 
    $configFile = __DIR__. "/bundleconfig.json";
    $config = json_decode(file_get_contents($configFile),true);

    $cmds = null;
    foreach ($config as $section=>$bundles){
        if (isset($bundles[$bundle_name])){
            $cmds = $bundles[$bundle_name]['commands'];
            break;
        }
    }
    if (!$cmds){
        echo "no config commands found for $bundle_name\n";
        return ['status' => 'fail', 'message' => 'bundle not found in config file'];
    }

    foreach($cmds as $c){
        $runCmds = "cd" . escapeshellarg($tmp) . "&&" . $c;
        exec($runCmds, $output, $result);

        if ($result !== 0){
            echo "command failed $c\n";
            return ['status' => 'fail', 'message' => 'install command failed'];
        }

        echo "bundle installed successfully\n";
        sendStatus($bundle_name, $version, "passed", $cluster);
        return ['status' => 'success', 'message' => 'bundle installed'];
    }

    echo "bundle test success\n";
    sendStatus($bundle_name, $version, "passed", $cluster);
    return ['status' => 'fail', 'message' => 'bundle installed'];
    */

}


// --- REQUEST PROCESSOR ---
/* same logic from rmqserver.php altered for deployment queues. 
    tried to make it accomodate for full bundler script but will probably have to add/change some things
*/

// decides which function to run
function requestProcessor($req)
{
    echo "--------------------------\n";
    echo "Received install request:\n";
    var_dump($req);
    flush();

    if (!isset($req['type'])) {
        return ['status' => 'fail', 'message' => 'no type'];
    }

    switch ($req['type']) {
        case 'install_bundle':
            return installBundle($req);
        // will need to add other cases for the other deployment functions when files are put into 1 full bundler script

        default:
            return ['status' => 'fail', 'message' => 'unknown type'];
    }
}

echo "Installer ready, waiting for requests\n";
flush();

// multi-queue capable version of the queue

// uses pcntl_fork -->  https://www.php.net/manual/en/function.pcntl-fork.php

// BUILD QUEUE NAME TO LISTEN ON

// WHICH CLUSTER ?
$clustername = null;
$whichCluster = [
    '172.29' => 'QA',
    '172.30' => 'Prod'
];
foreach ($whichCluster as $clusterIP => $clusterIP_name) {
    $shellcmd = "hostname -I | grep $clusterIP";
    exec($shellcmd, $output, $returnCode);
    if ($returnCode === 0) {
        $clustername = $clusterIP_name;
        break;
    }
}

$hostname = null;
$whichHost = [
    'frontend',
    'backend',
    'dmz'
];
foreach ($whichHost as $host) {
    $shellcmd = "hostname | grep $host";
    exec($shellcmd, $output, $returnCode);
    if ($returnCode === 0) {
        $hostname = $host;
        break;
    }
}



$whichQueue = 'deploy' . $clustername . $hostname;
//echo "QUEUE:" . $whichQueue; //DEBUGGING
$which = $argv[1] ?? $whichQueue ?? 'deployVersion';
$iniPath = __DIR__ . "/host.ini";

if ($which === 'all') { // to run all queues when scripts are together later
    echo "Bundler server starting for ALL deployment queues...\n";
    $sections = [
        'deployQAfrontend',
        'deployQAbackend',
        'deployQAdmz',
        'deployProdfrontend',
        'deployProdbackend',
        'deployProddmz',
    ]; // may need to add / change..? unsure

    foreach ($sections as $section) {
        $pid = pcntl_fork(); // process control fork; creats child process 
        if ($pid == -1) {
            die("Failed to fork for {$section}\n");
        } elseif ($pid === 0) {
            // child process
            echo "Listening on {$section}\n";
            $server = new rabbitMQServer($iniPath, $section);
            $server->process_requests('requestProcessor');
            exit(0);
        }
    }

    // parent waits for all children
    while (pcntl_wait($status) > 0) {
    }
} else {
    echo "Bundler server starting for queue section: {$which}\n";
    $server = new rabbitMQServer($iniPath, $which);
    echo "Connecting to queue: {$which}\n";
    flush();
    $server->process_requests('requestProcessor');
    echo "Bundler server stopped for {$which}\n";
}
?>