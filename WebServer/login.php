<?php
session_start();
require_once 'rabbitMQLib.inc';


// Accept either uname/pword (form) or username/password (API)
$username = $_POST['uname'] ?? $_POST['username'] ?? '';
$password = $_POST['pword'] ?? $_POST['password'] ?? '';

if ($username === '' || $password === '') {
	echo json_encode(['status'=>'error','message'=>'Missing username or password']);
	exit;
}



$client = new rabbitMQClient(__DIR__ . '/../testRabbitMQ.ini', 'testServer');

$request = [
	'type' => 'login',
	'username' => $username,
	'password' => $password,
];

$response = $client->send_request($request);

if (is_array($response) && isset($response['status']) && $response['status'] === 'success') {
	// set PHP session for web user
	$_SESSION['login'] = true;
	$_SESSION['uname'] = $username;
	if (isset($response['session_key'])) {
		$_SESSION['session_key'] = $response['session_key'];
	}
}

echo json_encode($response);
exit;

?>