<?php
// REGISTER PAGE
// Should use RabbitMQPHP (AMQP connection protocol) to send information between the user and database.
// pulled from Chizzy's branch

session_start();
require_once __DIR__ . '/../../rabbitMQ/rabbitMQLib.inc';  
require_once __DIR__ . '/../../rabbitMQ/get_host_info.inc'; 
// changed above to expand to absolute path



// only allowing post submissions from the form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo "Use POST";
  exit;
}

$email    = $_POST['emailAddress'] ?? '';
$username = $_POST['username']     ?? '';
$password = $_POST['password']     ?? '';

// checking all of the fields are filled
if ($email === '' || $username === '' || $password === '') {
  http_response_code(400);
  echo "All fields are required.";
  exit;
}
// reworked above bcs variables were mismatched


// hash the password before sending through server and datbase

$hashedPassword = password_hash($password, PASSWORD_BCRYPT); // BCRYPT is an algorithm for hashing, supposedly more secure than SHA256



$request = [
  'type'     => 'register',
  'email'    => $email,
  'username' => $username,
  'password' => $hashedPassword,
];


try {
  // connect to rmq
 $client = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'AuthRegister');
 // changed to reflect the new section name

  // sending the registration request
  $response = $client->send_request($request); // changed to correct variable name

  // response handling
  if (is_array($response) && ($response['status'] ?? '') === 'success') {
    header("Location: index.php?register_success=1");
    exit;
  } 
  else {
    $msg = is_array($response) ? urlencode($response['message'] ?? 'Unknown error') : 'No response from server';
    header("Location: index.php?register_error=$msg");
    exit;
    }
}
catch (Exception $e) {
  echo "Error connecting to RabbitMQ: " . $e->getMessage();
}
?>