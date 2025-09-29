<?php
// register.php - forward registration form to RabbitMQ worker and return JSON
header('Content-Type: application/json; charset=utf-8');

$email = $_POST['emailAddress'] ?? '';
$username = $_POST['uname'] ?? ($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $username === '' || $password === '') {
    echo json_encode(['status'=>'error','message'=>'Missing registration fields']);
    exit;
}

// include rabbit library (adjust path if necessary)
if (file_exists(__DIR__ . '/../rabbitMQLib.inc')) {
    require_once __DIR__ . '/../rabbitMQLib.inc';
} else {
    require_once 'rabbitMQLib.inc';
}

$client = new rabbitMQClient(__DIR__ . '/../testRabbitMQ.ini', 'testServer');

$request = [
    'type' => 'register', 
    'email' => $email,
    'username' => $username,
    'password' => $password, // send plaintext; worker will hash
];

$response = $client->send_request($request);

echo json_encode($response);
exit;
