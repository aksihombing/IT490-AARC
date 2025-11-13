#!/usr/bin/php
<?php

// get argument to know the bundle name
$bundle_name = $argv[1];
// the deployment listener will be the one to update the name of the bundle by checking its own database

// connect to rmq
try {
    $client = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'BundleSend'); // need to verify WHERE the bundle script itself will live + make sure host.ini includes the new queue + host specific to the deployment vm

    $request = [
        'type' => 'set_bundle',
        'bundle_name' => $bundle_name
    ];


    // build + send request
    $response = $client->send_request($request);

    if ($response['status'] === 'success') {
        echo "Failure to send bundle to deployment listener script\n";
        exit(0);
    }

} catch (Exception $e) {
    echo "Failure to send bundle to deployment listener script: " . ($e->getMessage());
    exit(1);
}

// maybe echo message out



?>