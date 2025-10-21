<?php
// /http/library_add.php add a book to user's library, request handler 
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['session_key']) || empty($_SESSION['uid'])) {
  http_response_code(401);
  echo json_encode(['status'=>'error','message'=>'Not logged in']);
  exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['status'=>'error','message'=>'Use POST']);
  exit;
}

$in = json_decode(file_get_contents('php://input'), true) ?? [];
$works_id = trim($in['works_id'] ?? '');
if ($works_id === '') {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'Missing works_id']);
  exit;
}

require_once __DIR__ . '/../rabbitMQ/rabbitMQLib.inc';

try {
  $client = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'LibraryAdd');
  $resp = $client->send_request([
    'type'     => 'library.personal.add',
    'user_id'  => (int)$_SESSION['uid'],
    'works_id' => $works_id,
  ]);

  if (is_array($resp) && ($resp['status'] ?? '') === 'success') {
    echo json_encode(['status'=>'success','message'=>$resp['message'] ?? 'added']);
  } else {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$resp['message'] ?? 'add failed']);
  }
} catch (Exception $e) {
  http_response_code(502);
  echo json_encode(['status'=>'error','message'=>'Upstream error: '.$e->getMessage()]);
}
