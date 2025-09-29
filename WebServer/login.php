<?php
session_start();
require_once('rabbitMQLib.inc');



// l `if (!isset($_POST))` is always false
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode("Login Failed: bad method");
  exit;
}

// Accept uname/pword (from our JS) or username/password
$username = $_POST['username'] ?? ($_POST['uname'] ?? '');
$password = $_POST['password'] ?? ($_POST['pword'] ?? '');

if ($username === '' || $password === '') {
  echo json_encode("Login Failed: Missing credentials");
  exit;
}

try {
  $client  = new rabbitMQClient("testRabbitMQ.ini","testServer");
  $request  = ['type' => 'login', 'username' => $username, 'password' => $password];
  $response = $client->send_request($request);

  if (is_array($response) && ($response['status'] ?? '') === 'success') {
    $_SESSION['login']       = true;
    $_SESSION['uname']       = $username;
    $_SESSION['session_key'] = $response['session_key'] ?? null; // server/DB generates it
    
    echo json_encode("Login Success.");
  } else {
    $msg = is_array($response) ? ($response['message'] ?? 'Invalid credentials') : 'No response';
    echo json_encode("Login Failed: " . $msg);
  }
} catch (Exception $e) {
  echo json_encode("Login Failed: " . $e->getMessage());
}
?>
