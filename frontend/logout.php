<?php
session_start();
require_once __DIR__ . '/rabbitMQ/rabbitMQLib.inc';
require_once __DIR__ . '/rabbitMQ/get_host_info.inc';

if (!isset($_SESSION['session_key'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$request = [
    'type' => 'logout',
    'session_key' => $_SESSION['session_key']
];

try {
    $client = new rabbitMQClient(__DIR__ . "/rabbitMQ/host.ini", "AuthLogout");
    $response = $client->send_request($request);
} catch (Exception $e) {
    // will still log out even if rmq fails i think
}

session_destroy();
header("Location: index.php");
exit;
