#!/usr/bin/php
<?php
require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/get_host_info.inc';
require_once __DIR__ . '/get_path_info.inc';

// https://www.php.net/manual/en/function.shell-exec.php
// https://www.php.net/manual/en/function.exec.php



// CHECK WHICH VM THIS BUNDLE SCRIPT IS ON 
$checkIP = trim(shell_exec("hostname -I | awk '{print $1}'"));
$whichVM = [
    '172.28.108.126' => 'Frontend',
    '172.28.219.213' => 'Backend',
    '172.28.109.126' => 'DMZ'
];
$section = $whichVM[$ip] ?? null;
echo "Running on VM section: $section";


// connect to rmq
// get argument to know the bundle name

// the deployment listener will be the one to update the name of the bundle by checking its own database
try {
    $client = new rabbitMQClient(__DIR__ . '/deployQueues.ini', 'DeployVersion'); // need to verify WHERE the bundle script itself will live + make sure host.ini includes the new queue + host specific to the deployment vm

    $request = [
        'type' => 'set_bundle',
        'bundle_name' => $bundle_name
    ];


    // build + send request
    $response = $client->send_request($request);

    if (isset($response['status']) && $response['status'] === 'success') {
        echo "Successfully received response from remote\n";
        exit(0);
    }

    echo "Error: Bundle not sent\n";
    exit(1);

} catch (Exception $e) {
    echo "Failure to send bundle to deployment listener script: " . ($e->getMessage());
    exit(1);
}



?>