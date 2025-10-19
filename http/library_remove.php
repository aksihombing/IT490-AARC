<?php
// /http/library_remove.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['session_key']) || !isset($_SESSION['uid'])) {
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
$works_id = $in['works_id'] ?? null;
if (!$works_id) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'Missing works_id']);
  exit;
}

require_once __DIR__ . '/../rabbitMQ/rabbitMQLib.inc';
$client = new rabbitMQClient(__DIR__.'/../rabbitMQ/host.ini', 'LibraryRemove'); // <- DB queue

$req = [
  'type'     => 'library.remove', //new queue type
  'user_id'  => (int)$_SESSION['uid'],
  'works_id' => (string)$works_id,
];

try {
  $resp = $client->send_request($req);
  if (is_array($resp) && ($resp['status'] ?? '') === 'success') {
    echo json_encode(['status'=>'success']);
  } else {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$resp['message'] ?? 'Remove failed']);
  }
} catch (Exception $e) {
  http_response_code(502);
  echo json_encode(['status'=>'error','message'=>'Upstream error: '.$e->getMessage()]);
}
