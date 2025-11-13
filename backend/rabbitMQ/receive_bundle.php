#!/usr/bin/php
<?php
require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/get_host_info.inc';

function setBundle(array $req){
    /*    $request = [
        'type' => 'set_bundle',
        'bundle_name' => $bundle_name
    ]; */

    $bundle_name = $req['bundle_name'];
    echo "Bundle name {$bundle_name}\n";

}




function requestProcessor($req) {
  echo "----------------------\n";
  echo "Received request:\n";
    var_dump($req);
    flush();
  
  if (!isset($req['type'])) {
    return ['status'=>'fail','message'=>'no type'];
  }

  switch ($req['type']) {
    case 'set_bundle': return setBundle($req);
    default: return ['status'=>'fail','message'=>'unknown type'];
  }
}

echo "Auth server ready, waiting for requests\n";
flush();

// multi-queue capable version of the queue
$which = $argv[1] ?? 'all';
$iniPath = __DIR__ . "/host.ini";

if ($which === 'all') { // to run all queues for DB and RMQ connection
    echo "Auth server starting for ALL queues...\n";
    $sections = ['AuthRegister', 'AuthLogin', 'AuthValidate', 
      'AuthLogout', 'LibraryPersonal', 'LibraryRemove', 
      'CreateReviews','ListReviews','LibraryAdd','ClubProcessor', 'LibrarySearch', 'LibraryDetails'];
      // LibraryCollect is for the specific connection between database > rmq > api

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
    echo "Auth server starting for queue section: {$which}\n";
    $server = new rabbitMQServer($iniPath, $which);
    echo "Connecting to queue: {$which}\n";
    flush();
    $server->process_requests('requestProcessor');
    echo "Auth server stopped for {$which}\n";
}

?>