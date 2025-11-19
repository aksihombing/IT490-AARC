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

function sendStatus(string $bundle_name, int $version, string $status, string $cluster){
    try{
        $client = new rabbitMQClient(__DIR__ . '/host.ini', 'deployStatus');

        $status_map = [
            'type' => 'send_status',
            'bundle_name' => $bundle_name,
            'version' => $version,
            'status' => $status, // passed or failed
            'cluster' => $cluster //qa or prod
        ];
        
        $response = $client->send_request($req);
        echo "status sent to deployment:\n";
        var_dump($response);
    } catch(Exception $e){
        echo "failed to send status to deployment: ". $e->getMessage;
    }
}

function installBundle(array $req){
    $bundle_name = $req['bundle_name'] ?? '';
    $version = $req['version'] ?? '';
    $tar = $req['tar_name'] ?? '';

    if (!$bundle_name || !$version || !$tar){
        return ['status' => 'fail', 'message' => 'missing install requirements'];
    }

    $bundleDir = "/var/www/bundles";
    $bundleFile = $bundleDir . "/". $tar;

    if (!file_exists($bundleFile)){ // bundle needs to exist to be installed
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
    $cmd = "tar -xzf". escapeshellarg($bundleFile) . "-C". escapeshellarg($tmp);
    exec($cmd, $output, $result);

    if ($result !== 0){
        echo "bundle extraction failed\n";
        sendStatus($bundle_name, $version, "failed", $cluster);
        return ['status' => 'fail', 'message' => 'tar extraction failed'];
    }

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
        $runCmds = "cd"> escapeshellarg($tmp) . "&&". $c;
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

}


// --- REQUEST PROCESSOR ---
/* same logic from rmqserver.php altered for deployment queues. 
    tried to make it accomodate for full bundler script but will probably have to add/change some things
*/

// decides which function to run
function requestProcessor($req) {
  echo "Received install request:\n";
    var_dump($req);
    flush();
  
  if (!isset($req['type'])) {
    return ['status'=>'fail','message'=>'no type'];
  }

  switch ($req['type']) {
    case 'install_bundle': return installBundle($req);
    // will need to add other cases for the other deployment functions when files are put into 1 full bundler script

    default: return ['status'=>'fail','message'=>'unknown type'];
  }
}

echo "Installer ready, waiting for requests\n";
flush();

// multi-queue capable version of the queue

// uses pcntl_fork -->  https://www.php.net/manual/en/function.pcntl-fork.php
$which = $argv[1] ?? 'deployQAbackend';
$iniPath = __DIR__ . "/host.ini";

if ($which === 'all') { // to run all queues when scripts are together later
    echo "Bundler server starting for ALL deployment queues...\n";
    $sections = ['deployQA','deployProd','deployVersion','deployStatus']; // may need to add / change..? unsure

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
    while (pcntl_wait($status) > 0) {}
} else {
    echo "Bundler server starting for queue section: {$which}\n";
    $server = new rabbitMQServer($iniPath, $which);
    echo "Connecting to queue: {$which}\n";
    flush();
    $server->process_requests('requestProcessor');
    echo "Bundler server stopped for {$which}\n";
}
?>