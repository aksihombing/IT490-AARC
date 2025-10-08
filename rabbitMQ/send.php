#!/usr/bin/php
<?php
// PRODUCER/PUBLISHER/CLIENT - Sends messages into a queue
// Typically used by webserver
// https://www.rabbitmq.com/tutorials/tutorial-one-php -- Reference

require_once('rabbitMQLib.inc'); // also references get_host_info.inc
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;



function send_request($data){
    // conection to AMQP Stream
    $connection = new AMQPStreamConnection('172.28.219.213', 5672, 'saas_user', 'p@ssw0rd');
    $channel = $connection->channel();

    /*
    Parameters explained for queues:
    QUEUE (string) -- name of queue
    DURABLE (bool) -- true = queue survives broker restart; persistent
    EXCLUSIVE (bool) -- true = only accessed by current connection and deleted when connection closes
    AUTO_DELETE (bool) -- true = queue is deleted when last consumer unsubscribes from queue
    [OPTIONAL ARGUMENTS]
    */

    list($callback_queue,,) = $channel->queue_declare("",false, false, true, false);
    $correlation_id = uniqid(); // is used for the client to retrieve the correct response from the reply queue. correlation_id should always be uniquely generated
    $msg = new AMQPMessage(
        json_encode($data),
        [
            'correlation_id' => $correlation_id,
            'reply_to' => $callback_queue // makes sure the reply gets sent to the right place
        ]
    );

    /*
    // message payload - SCRAPPED
    $data = [
        'action' =>  'register',
        'username' => $_POST['username'] ?? '', // ternary thingy i took from login.php
        'password' => $_POST['password'] ?? '',
        'email' => $_POST['email'] ?? ''
    ];
    */
    //$msg = new AMQPMessage(json_decode($data));

    $channel -> basic_publish($msg,'','rpc_queue'); // RPC = remote procedure call

    $response = null;
    $callback = function($rep) use (&$response, $correlation_id) {
        if ($rep->get('correlation_id') == $correlation_id){
            $response = $rep->body;
        }
    };
    // debugging
    //echo " [x] Sent request to queue'\n";

    $channel->basic_consume($callback_queue,'',false,true,false,false,$callback);
    
    //wait for response
    while (!$response){
        $channel->wait();
    }

    $channel->close();
    $connection->close();

    return json_decode($response, true);
}
?>