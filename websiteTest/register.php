<?php
// REGISTER PAGE
// Should use RabbitMQPHP (AMQP connection protocol) to send information between the user and database.
// partial code taken from testRabbitMQClient.php


require_once __DIR__ . '/vendor/autoload.php'; // php-amqplib


//require_once('rabbitMQLib.inc')


// Check if user info is collected
// if not collected properly, variable is empty.
if (isset($_POST['emailAddress'])) {
    $email = $_POST['emailAddress'];
} else {
    $email = ''; // if NOT set, string is EMPTY.
}
if (isset($_POST['uname'])) {
    $username = $_POST['uname'];
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
    exit("ERROR: Post for registration info failed.");
}


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
$request['uname'] = $username;
$request['password'] = $password;
$request['message'] = $msg;
$response = $client->send_request($request);
//$response = $client->publish($request);

echo "client received response: ".PHP_EOL;
print_r($response);
echo "\n\n";

echo $argv[0]." END".PHP_EOL;


?>