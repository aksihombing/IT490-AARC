<?php
// CHIZZY'S BASE CODE with edits from Rea for rabbitMQClient functions
session_start();
require_once(__DIR__ . '/../rabbitMQ/rabbitMQLib.inc');
require_once(__DIR__ . '/../rabbitMQ/get_host_info.inc');
// another option is to use .htaccess to configure a "block" or prevent access to specific files directly.


// l `if (!isset($_POST))` is always false
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode("Login Failed: bad method");
  exit;
}

// Accept username/password (from our JS) or username/password
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
  http_response_code(400);
  echo json_encode("Login Failed: Missing credentials. All fields required.");
  exit;
}

$request = [
  'type' => 'login',
  'username' => $username,
  'password' => $password
];

try {
  $client = new rabbitMQClient(__DIR__ . '/../host.ini', "AuthLogin");

  $response = $client->send_request($request); // sends request and waits for response

  if (is_array($response) && ($response['status'] ?? '') === 'success') {
    $_SESSION['login'] = true;
    $_SESSION['username'] = $response['username'] ?? $username;
    $_SESSION['session_key'] = $response['session_key'] ?? ''; // server/DB generates it via db_functions.php createSession() function
    $_SESSION['user_id'] = $response['uid'] ?? 0;

    header("Location: index.php");
    echo json_encode("Login Success.");
    exit;
  } 
  else {
    $msg = $response['message'] ?? 'Invalid login';
    header("Location: index.php?error=" . urlencode($msg));
    exit;
  }
} 
catch (Exception $e) {
  echo "Error connecting to RabbitMQ: " . $e->getMessage();
}
?>