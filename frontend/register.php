<?php
// REGISTER PAGE
// Should use RabbitMQPHP (AMQP connection protocol) to send information between the user and database.
// pulled from Chizzy's branch

require_once('../rabbitMQ/RabbitMQServer.php');
require_once('../rabbitMQ/rabbitMQLib.inc');


session_start();

if (isset($_POST['emailAddress'])) { //like an exapnded version of the ternary thing
    $email = $_POST['emailAddress'];
} else {
    $email = ''; // if NOT set, string is EMPTY.
}
if (isset($_POST['username'])) {
    $username = $_POST['username'];
} else {
    $username = '';
}
if (isset($_POST['password'])) {
    $password = $_POST['password'];
} else {
    $password = '';
}
// validation check for empty variables :
if (empty($emailAddress) || empty($username) || empty($password)) {
    exit("ERROR: Post for registration info failed. Strings set to EMPTY");
}



// hash the password before sending through server and datbase

$hashedPassword = password_hash($password, PASSWORD_BCRYPT); // BCRYPT is an algorithm for hashing, supposedly more secure than SHA256



 $request = [
        'action'   => 'register',
        'email'    => $email,
        'username' => $username,
        'password' => $hashedPassword,

    ];

// try to connect to RabbitMQ
try {
    $client = new rabbitMQClient("testRabbitMQ.ini","testServer"); 
// uses testRabbitMQ.ini for RABBIT SERVER CONFIGURATION. 
// REGISTRATION info will get put into Database via the information set in testRabbitMQ.ini !!!

    $response = $client->send_request($request);

    

  if (is_array($response) && ($response['status'] ?? '') === 'success') {
    echo "Registration success. You can now log in.";

    
  } else {
    $msg = is_array($response) ? ($response['message'] ?? 'Unknown error') : 'No response from server';
    echo "Registration failed: $msg";

  }
} catch (Exception $e) {
    echo "Error connecting to RabbitMQ: " . $e->getMessage();
}