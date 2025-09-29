#!/usr/bin/php
<?php
require_once('rabbitMQLib.inc');

$client = new rabbitMQClient("testRabbitMQ.ini","testServer");


// register: php testRabbitMQClient.php register email username password
// login  : php testRabbitMQClient.php "message" username password
if (isset($argv[1]) && $argv[1] === 'register') {
	$email = $argv[2] ?? '';
	$username = $argv[3] ?? '';
	$password = $argv[4] ?? '';
	if ($email === '' || $username === '' || $password === '') {
		echo "Usage: php testRabbitMQClient.php register email username password\n";
		exit(1);
	}
	$request = [
		'type' => 'register',
		'email' => $email,
		'username' => $username,
		'password' => $password,
	];
	$response = $client->send_request($request);
	echo json_encode($response) . PHP_EOL;
	exit;
}

$msg = isset($argv[1]) ? $argv[1] : "test message";
$username = isset($argv[2]) ? $argv[2] : "steve";
$password = isset($argv[3]) ? $argv[3] : "password";

$request = array();
$request['type'] = "login";            
$request['username'] = $username;
$request['password'] = $password;
$request['message'] = $msg;

$response = $client->send_request($request);

// Print the response as JSON to match other client files
echo json_encode($response) . PHP_EOL;


