<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['session_key']) || empty($_SESSION['uid'])) {
  http_response_code(401);
  echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
  exit;
}

require_once __DIR__ . '/../rabbitMQ/rabbitMQLib.inc';

// Accept either {comment: "..."} or {body: "..."} from frontend
$in       = json_decode(file_get_contents('php://input'), true) ?? [];
$works_id = $in['works_id'] ?? ($in['work_olid'] ?? null);
$rating   = isset($in['rating']) ? (int)$in['rating'] : null;
$body     = trim($in['comment'] ?? ($in['body'] ?? ''));

if (!$works_id || !$rating || $rating < 1 || $rating > 5) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'Missing works_id or rating (1–5)']);
  exit;
}

$user_id = $_SESSION['uid'];
$client  = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', "CreateReviews"); // DB listener queue

$request = [
  'type'     => 'library.create_review', // new queue type
  'user_id'  => $user_id,
  'works_id' => $works_id,
  'rating'   => $rating,
  'body'     => $body
];

$response = $client->send_request($request);
echo json_encode($response);
