#!/usr/bin/php
<?php
require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/get_host_info.inc';

// get argument to know the bundle name
$bundle_name = $argv[1];
// the deployment listener will be the one to update the name of the bundle by checking its own database

// connect to rmq
try {
    $client = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'AuthValidate'); // need to verify WHERE the bundle script itself will live + make sure host.ini includes the new queue + host specific to the deployment vm

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

// maybe echo message out



?>