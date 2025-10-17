<?php
// /api/library_collect.php
session_start();
header("Content-Type: application/json");

// 1) Auth (use session_key consistently)
if (!isset($_SESSION['session_key'])) {
  http_response_code(401);
  echo json_encode(["status"=>"error","message"=>"Unauthorized"]);
  exit;
}

// 2) Method check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(["status"=>"error","message"=>"Method not allowed; use POST"]);
  exit;
}

require_once __DIR__ . '/../rabbitMQ/rabbitMQLib.inc';

// 3) Read JSON body
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// 4) Validate input
$action   = $input['action']   ?? null;  // "add" | "remove"
$works_id = $input['works_id'] ?? null;  // e.g. "OL12345W"
if (!$action || !$works_id) {
  http_response_code(400);
  echo json_encode(["status"=>"error","message"=>"Missing action or works_id"]);
  exit;
}
if (!in_array($action, ['add','remove'], true)) {
  http_response_code(400);
  echo json_encode(["status"=>"error","message"=>"Action must be 'add' or 'remove'"]);
  exit;
}

// 5) User id from session (no prod fallback)
$userId = $_SESSION['uid'] ?? null;
if (!$userId) {
  http_response_code(401);
  echo json_encode(["status"=>"error","message"=>"No user id in session"]);
  exit;
}

// 6) RabbitMQ request
try {
  $client = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', "LibraryCollect");
  // Build the request payload to send through RabbitMQ
$request = [
  'type'      => 'library.collect', // tells the DB listener what to do
  'user_id'   => $userId,           // identifies which user this action is for
  'works_id' => $workId,           // the unique book ID from Open Library (ex: OL12345W)
  'action'    => $action            // adding and removing? I think thats what this queue is for?
];
  $resp = $client->send_request($request);

  if (is_array($resp) && ($resp['status'] ?? '') === 'success') {
    echo json_encode(["status"=>"success","message"=>$resp['message'] ?? 'ok']);
  } else {
    http_response_code(400);
    echo json_encode([
      "status"=>"error",
      "message"=> is_array($resp) ? ($resp['message'] ?? 'Request failed') : 'No response from service'
    ]);
  }
} catch (Exception $e) {
  http_response_code(502);
  echo json_encode(["status"=>"error","message"=>"Upstream error: ".$e->getMessage()]);
}

?>