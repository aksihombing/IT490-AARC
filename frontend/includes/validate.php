<?php
session_start();
require_once(__DIR__ . '/../rabbitMQ/rabbitMQLib.inc');


$client = new rabbitMQClient(__DIR__ . "/../host.ini", "AuthValidate");

// check for existing session key
$sessionKey = $_SESSION['session_key'] ?? null;
$userData = null;

if ($sessionKey) { // validate session key
  $response = $client->send_request([
    'type' => 'validate',
    'session_key' => $sessionKey
  ]);

  if ($response['status'] === 'success') {
    $userData = $response['user']; // saves user data
  } else {
    // invalid or expired session
    unset($_SESSION['session_key']);
  }
}
?>