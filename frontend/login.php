<?php
// CHIZZY'S BASE CODE with edits from Rea for rabbitMQClient functions
session_start();
require_once(__DIR__.'/../rabbitMQ/rabbitMQLib.inc');
// another option is to use .htaccess to configure a "block" or prevent access to specific files directly.


// l `if (!isset($_POST))` is always false
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode("Login Failed: bad method");
  exit;
}

// Accept username/password (from our JS) or username/password
$username = $_POST['username'] ?? ($_POST['username'] ?? '');
$password = $_POST['password'] ?? ($_POST['password'] ?? '');

if ($username === '' || $password === '') {
  echo json_encode("Login Failed: Missing credentials");
  exit;
}

try {
  $client  = new rabbitMQClient(__DIR__.'/../host.ini',"testServer");
  $request  = [
    'type' => 'login', 
    'username' => $username, 
    'password' => $password
];
  $response = $client->send_request($request); // sends request and waits for response

  if (is_array($response) && ($response['status'] ?? '') === 'success') {
    $_SESSION['login']       = true;
    $_SESSION['username']       = $username;
    $_SESSION['session_key'] = $response['session_key'] ?? null; // server/DB generates it
    
    header("Location: index.php");
    echo json_encode("Login Success.");
    exit;
  } 
  else {
    $msg = is_array($response) ? ($response['message'] ?? 'Invalid credentials') : 'No response';
    echo json_encode("Login 
    Failed: " . $msg);
    header("Location: index.php?error".urlencode($msg));
    exit;
  }
} catch (Exception $e) {
  echo json_encode("Login Failed: " . $e->getMessage());
  header("Location: index.php?error".urlencode($e->getMessage()));
}
?>
