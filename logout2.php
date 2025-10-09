<?php
session_start();
require_once 'rabbitMQLib.inc';

$sessionKey = $_SESSION['session_key'] ?? '';
if ($sessionKey !== '') {
  try {
    $client  = new rabbitMQClient("testRabbitMQ.ini","testServer");
    $client->send_request(['type' => 'logout', 'session_key' => $sessionKey]);
  } catch (Exception $e) {
    // ignore; still clear local session
  }
}

$_SESSION = [];
session_destroy();
header('Location: index.php');
exit;
