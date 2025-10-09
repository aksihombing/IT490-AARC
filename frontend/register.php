<?php
// REGISTER PAGE
// Should use RabbitMQPHP (AMQP connection protocol) to send information between the user and database.
// pulled from Chizzy's branch


require_once(__DIR__.'/../rabbitMQ/rabbitMQLib.inc');




session_start();


// CHIZZY's EXCERPT:
// uses email + username; accept both username/username
$email    = $_POST['email'] ?? '';
$username = $_POST['username']     ?? ($_POST['username'] ?? '');
$password = $_POST['password']     ?? '';

if ($email === '' || $username === '' || $password === '') {
  echo json_encode("Registration failed: missing fields");
  exit;
}

// --------------


// hash the password before sending through server and datbase
$hashedPassword = password_hash($password, PASSWORD_BCRYPT); // BCRYPT is an algorithm for hashing, supposedly more secure than SHA256



$request = [
    'type'   => 'register',
    'email'    => $email,
    'username' => $username,
    'password' => $hashedPassword,

];

// try to connect to RabbitMQ
try {

    // AMQP Connection
    $client  = new rabbitMQClient("host.ini","testServer");
    $response = $connection->send_request($request);

  if (is_array($response) && ($response['status'] ?? '') === 'success') {
    echo "Registration success. You can now log in.";
    
  } else {
    $msg = is_array($response) ? ($response['message'] ?? 'error') : 'No response from server';
    echo "Registration failed: $msg";

  }
} catch (Exception $e) {
    echo "Error connecting to RabbitMQ: " . $e->getMessage();
}