#!/usr/bin/php
<?php
// PRODUCER/PUBLISHER/CLIENT - Sends messages into a queue
// https://www.rabbitmq.com/tutorials/tutorial-one-php -- Reference

require_once('rabbitMQLib.inc'); // also references get_host_info.inc
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


$db = new mysqli('172.28.172.114','testUser','12345','testdb');

// conection to AMQP Stream
$connection = new AMQPStreamConnection($BROKER_HOST, 5672, $USER, $PASSWORD);
$channel = $connection->channel();

$channel->queue_declare($queue, false, false, false, false);
/*
PARAMETERS EXPLAINED:
QUEUE (string) -- name of queue
DURABLE (bool) -- true = queue survives broker restart; persistent
EXCLUSIVE (bool) -- true = only accessed by current connection and deleted when connection closes
AUTO_DELETE (bool) -- true = queue is deleted when last consumer unsubscribes from queue
[OPTIONAL ARGUMENTS]
*/

//$msg = new AMQPMessage('Hello World!');
//$channel->basic_publish($msg, '', 'hello');



// message payload or whatever
$data = [
    'type' =>  'register',
    'username' => $_POST['username'] ?? '', // ternary thingy i took from login.php
    'password' => $_POST['password'] ?? '',
    'email' => $_POST['email'] ?? ''
];

$msg = new AMQPMessage(json_decode($data));
$channel -> basic_publish($msg,'',$queue);

// debugging
echo " [x] Sent request to queue'\n";

$channel->close();
$connection->close();

?>