<?php
session_start();
require_once __DIR__ . '/../rabbitMQ/rabbitMQLib.inc';
header('Content-Type: application/json');

if (!isset($_SESSION['uid'])) {
  http_response_code(401);
  echo json_encode(['status' => 'fail', 'message' => 'Not logged in']);
  exit;
}

$userId = $_SESSION['uid'];
$client = new rabbitMQClient(__DIR__.'/../rabbitMQ/host.ini', "LibraryUsers");

$response = $client->send_request([
   'type'    => 'library.users',  // request to get all books for a specific user
  'user_id' => $userId,          // identifies which user's library we want to fetch (comes from their session ID)
  'limit'   => 200,              // limits how many books to return at once 
  'offset'  => 0                 // for pagination:start at the first record (0 = beginning of the list)
]);

echo json_encode($response);

?>