<?php 
session_start();
require_once __DIR__ . '/../rabbitMQ/rabbitMQLib.inc';
header('Content-Type: application/json');

// Check login
if (empty($_SESSION['session_key']) || empty($_SESSION['uid'])) {
  http_response_code(401);
  echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
  exit;
}

// Decode JSON body
$in = json_decode(file_get_contents('php://input'), true) ?? [];
$works_id = $_GET['works_id'] ?? ($in['works_id'] ?? ($in['works_id'] ?? null));
$rating   = isset($in['rating']) ? (int)$in['rating'] : null;
$body     = trim($in['body'] ?? '');

if (!$works_id || !$rating) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'Missing works_id or rating']);
  exit;
}

// Build request
$user_id = $_SESSION['uid'] ?? 1; // fallback for testing
$client  = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', "ListReviews");// may need changes

$request = [
  'type'     => 'library.review.list',// 
  'user_id'  => $user_id,
  'works_id' => $works_id,
  'rating'   => $rating,//will be ignored
  'body'     => $body// will be ignored
];

// Send to DB listener
$response = $client->send_request($request);

// Return response to frontend
echo json_encode($response);

?>