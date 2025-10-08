#!/usr/bin/php
<?php

//scrapped by rea


// CONSUMER - Receives messages from queue
// "Backend Processor"
// Connects to Broker (RabbitMQ Service VM)
// https://www.rabbitmq.com/tutorials/tutorial-one-php -- Reference

// TO SEE RABBITMQ QUEUES:
// sudo rabbitmqctl list_queues;
// DATABASE VM:
// php receive.php

require_once('rabbitMQLib.inc'); // also references get_host_info.inc
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


$connection = new AMQPStreamConnection('172.28.219.213', 5672, 'saas_user', 'p@ssw0rd');
$channel = $connection->channel();

$channel->queue_declare($queue, false, false, false, false);

// debugging
echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function (AMQPMessage $msg) {
  echo ' [x] Received ', $msg->getBody(), "\n"; 

  $data = json_decode($msg->body, true);

  $db = new mysqli('172.28.172.114','testUser','12345','testdb');

  if ($db->connect_error){ // connection error
    echo "DB connection failed: " . $db->connect_error . "\n";
    return;
  }

  // REGISTER
  if ($data['action'] === 'register'){
    // initializes info that was SENT
    $username = $data['username'];
    $password = password_hash($data['password'], PASSWORD_BCRYPT);
    $email = $data['email'];

    $stmt = $db->prepare("INSERT INTO users username, password, email VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $email);
    $stmt->execute();
    }
  elseif  ($data['action'] === 'login'){
    $username = $data['username'];
    $password = $data['password'];

    $stmt = $db->prepare("SELECT password FROM users WHERE username = ?"); // note that password isnt verified yet here
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($hash);
    $stmt->fetch();

    if ($hash && password_verify($password,$hash)){
        echo "LOGIN SUCCESSFUL for $username\n";
    }
    else {echo "INVALID LOGIN for $username\n";}
  }
  $msg->ack(); // ACKNOWLEDGEMENT !! 
  $db->close();
};

$channel->basic_consume('task_queue', '', false, true, false, false, $callback); // NOTE : idk what the queue would be called?

/*while ($channel->is_consuming()){
  $channel->wait();
}*/


try {
    $channel->consume();
} catch (\Throwable $exception) {
    echo $exception->getMessage();
}

// connection should stay open on the database for requests i think
//$channel->close();
//$connection->close();

?>