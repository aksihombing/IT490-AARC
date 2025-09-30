<?php
// Created by Rea S.

// REGISTER PAGE
// Should use RabbitMQPHP (AMQP connection protocol) to send information between the user and database.

// partial code taken from testRabbitMQClient.php


//require_once __DIR__ . '/vendor/autoload.php'; // php-amqplib
// not sure if this is needed????


//require_once('rabbitMQLib.inc')


session_start();

if (isset($_POST['emailAddress'])) {
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

    if ($response == false) { // if no response from RabbitMQ...
        echo "Registration failed. No response from server";
    }
    else {
        echo "Server Responded! : " . print_r($response,true);
    }

    /* 
    SCRAPPED !!!!
    // queue
    $channel->queue_declare( // declares a queue
        'register_queue', // name of queue
        false, // "Passive" --> when false, declares/starts the queue if it doesnt exist already.
        true, // "Durable" --> when true, data is stored on disk and can be recovered by RabbitMQPHP
        false, // "Exclusive" --> when false, queue can be accessed by any connection (standard in queues)
        false // "Auto Delete" --> when false, queue stays active even when empty or disconnected from consumers
    ); // i actually dont know if this is needed ???????
    */


} catch (Exception $e) {
    echo "Error connecting to RabbitMQ: " . $e->getMessage();
}

/*
// testRabbitMQClient.php ------------------

$client = new rabbitMQClient("testRabbitMQ.ini","testServer"); 
// uses testRabbitMQ.ini for RABBIT SERVER CONFIGURATION

if (isset($argv[1]))
{
  $msg = $argv[1];
}
else
{
  $msg = "test message";
}

$request = array();
$request['type'] = "Login";
$request['username'] = $username;
$request['password'] = $password;
$request['message'] = $msg;
$response = $client->send_request($request);
//$response = $client->publish($request);

echo "client received response: ".PHP_EOL; // PHP_EOL = End of Line character
print_r($response);
echo "\n\n";

echo $argv[0]." END".PHP_EOL;


?>
*/