#!/usr/bin/php
<?php

// Error reporting stuff. Idk everyone else is doing it.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);

// System-specific variables
require_once(__DIR__ . '/rabbitMQLib.inc');
require_once(__DIR__ . '/get_host_info.inc');

// These are the variables Aida's DepListener.php script needs.
$bundle = $argv[1];
$version = (int) $argv[2]; 	// Arguments are strings by default.
$status = $argv[3];
$cluster = $argv[4]; // Do we need this one?

$req = [
	'type' => 'status_update',
	'bundle_name' => $bundle,
	'version' => $version,
	'cluster' => $cluster // do we need?
	];

try {
	$client = new rabbitMQClient(__DIR__ . '/deployQueues.ini', 'DeployVersion');

	$response = $client->send_request($req);
} catch (Exception $e) {
	echo 'Failure to initialize RabbitMQ client and/or failure to send status update';
	exit(1);
}



?>
