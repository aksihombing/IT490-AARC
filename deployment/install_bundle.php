#!/usr/bin/php
<?php
/* VERY rough draft 
    just the logic for listening and installing bundles, will be implemented into 
    1 bundler script w all functions eventually

    the installer functions will only run on the qa and prod clusters. it listens
    for incoming messages from deployment to install bundles and also return status
    of the bundle back to deployment
*/
require_once __DIR__ . 'rabbitMQLib.inc';
require_once __DIR__ . 'get_host_info.inc';

function getBundlePath(string $bundle_name){
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

function sendStatus(string $bundle_name, int $version, string $status, string $cluster){
    $server = new rabbitMQServer(__DIR__ . '/host.ini', 'deployStatus');

    $status_map = [
        'bundle_name' => $bundle_name,
        'version' => $version,
        'status' => $status,
        'cluster' => $cluster
    ];
    
    //request logic here
}

function installBundle(array $req){
    $bundle_name = $req['bundle_name'] ?? '';
    $version = $req['version'] ?? ;
    $path = $req['path'] ?? '';

    if (!$bundle_name || !$version || !$path){
        return ['status' => 'fail', 'message' => 'missing install reqs'];
    }

    $bundleDir = getBundlePath($bundle_name);

    if ($bundleDir === null){
        return ['status' => 'fail', 'message' => 'unknown bundle type']; // if this shows up need to add a new section to the map for new bundle type?? or should i have it created here
    }
    
    $bundleFile = "/deployment/bundles/{$path}"; //unsure if this is the correct path :(

    if (!file_exists($bundleFile)){ // bundle needs to exist to be installed
        echo "bundle not found: $bundleFile\n";
        return ['status' => 'fail', 'message' => 'bundle missing'];
    }

    /*extract bundle

    https://www.php.net/manual/en/function.mkdir.php
    https://www.php.net/manual/en/function.uniqid.php
    https://systemd.io/TEMPORARY_DIRECTORIES/
    https://linuxvox.com/blog/linux-tmp-folder/
    */
    $tmp = "/tmp/deployment_extract" . uniqid();

    mkdir($tmp, , true); //figure out permission 
    //eugghhhh m confused

    //install bundle based on target directory + vm

    //test bundle??

    //change bundle status here?
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
    case 'install.bundle': return installBundle($req);
    // will need to add other cases for the other deployment functions when files are put into 1 full bundler script

    default: return ['status'=>'fail','message'=>'unknown type'];
  }
}

echo "Bundler ready, waiting for requests\n";
flush();

// multi-queue capable version of the queue

// uses pcntl_fork -->  https://www.php.net/manual/en/function.pcntl-fork.php
$which = $argv[1] ?? 'bundler';
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