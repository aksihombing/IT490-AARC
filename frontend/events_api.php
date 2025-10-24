<?php
require_once(__DIR__ . '/../rabbitMQ/rabbitMQLib.inc');
require_once(__DIR__ . '/../rabbitMQ/get_host_info.inc');

header('Content-Type: application/json');

$club_id = $_GET['club_id'] ?? 0;

if (!$club_id) {
  echo json_encode(['status'=>'fail','message'=>'missing club_id']);
  exit;
}

$req = [
  'type' => 'club.events.list',
  'club_id' => $club_id
];

$client = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'ClubProcessor');
$res = $client->send_request($req);
echo json_encode($res);
