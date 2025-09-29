<?php
require_once('rabbitMQLib.inc');


// uses emailAddress + uname; accept both uname/username
$email    = $_POST['emailAddress'] ?? '';
$username = $_POST['username']     ?? ($_POST['uname'] ?? '');
$password = $_POST['password']     ?? '';

if ($email === '' || $username === '' || $password === '') {
  echo json_encode("Registration failed: missing fields");
  exit;
}

try {
  $client  = new rabbitMQClient("testRabbitMQ.ini","testServer");


  $request  = ['type' => 'register', 'email' => $email, 'username' => $username, 'password' => $password];
  $response = $client->send_request($request);

  if (is_array($response) && ($response['status'] ?? '') === 'success') {
    echo json_encode("Registered.");
  } else {
    $msg = is_array($response) ? ($response['message'] ?? 'Unknown error') : 'No response';
    echo json_encode("Registration failed: " . $msg);
  }
} catch (Exception $e) {
  echo json_encode("Registration failed: " . $e->getMessage());
}
?>
