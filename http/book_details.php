<?php
// /var/www/http/book_details.php
session_start();
header('Content-Type: application/json');

if (!isset($_GET['works_id']) || $_GET['works_id']==='') {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'missing works_id']);
  exit;
}

$worksId = $_GET['olid'];

require_once __DIR__ . '/../rabbitMQ/rabbitMQLib.inc';

try {
  $client = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'LibraryDetails');
  $resp = $client->send_request([
    'type'     => 'book_details',
    'olid' => $worksId
  ]);

  if (is_array($resp) && ($resp['status'] ?? '') === 'success') {
    echo json_encode($resp);
  } else {
    http_response_code(502);
    echo json_encode(['status'=>'error','message'=>$resp['message'] ?? 'upstream failed']);
  }
} catch (Exception $e) {
  http_response_code(502);
  echo json_encode(['status'=>'error','message'=>'rmq error: '.$e->getMessage()]);
}
