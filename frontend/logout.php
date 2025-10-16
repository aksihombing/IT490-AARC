<?php
session_start();
$sessionKey = $_SESSION[$session_key];

if ($session_key) {
  $client  = new rabbitMQClient(__DIR__ . '/../host.ini', "AuthLogin");
  $request  = [
    'type' => 'logout',
    'session_key' => $session_key
  ];
  $response = $client->send_request($request); // sends request and waits for response

    // invalid or expired session
    unset($_SESSION['session_key']);
  
}


session_destroy();
header('Location: index.php');
exit;
