<?php
require_once(__DIR__ . '/../../rabbitMQ/rabbitMQLib.inc');
require_once(__DIR__ . '/../../rabbitMQ/get_host_info.inc');
require_once(__DIR__ . '/../../rabbitMQ/log_producer.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['action'] ?? '';
    $payload = $_POST;

    $map = [
      'create'        => 'club.create',
      'invite'        => 'club.invite',
      'event_create'  => 'club.events.create',
      'event_cancel'  => 'club.events.cancel',
      'list'          => 'club.list',       
      'events.list'   => 'club.events.list',
      'invite_link'   => 'club.invite_link'
    ];

    if (!isset($map[$type])) {
        echo json_encode(['status'=>'fail','message'=>'unknown function']);
        log_event("frontend", "error", "clubs_functions.php unknown action: " . $type);
        exit;
    }

    $payload['type'] = $map[$type];

    try {
        $client = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'ClubProcessor');
        $res = $client->send_request($payload);
        echo json_encode($res);
    } catch (Exception $e) {
        echo json_encode(['status'=>'fail','message'=>$e->getMessage()]);
        log_event("frontend","error","Book with Id: $olid with a user: $userId");
    }
    exit;
}
?>
