<?php
// events_functions 
// going to handle displaying events, creating, and canceling club events
session_start();
require_once(__DIR__ . '/../../rabbitMQ/rabbitMQLib.inc');
require_once(__DIR__ . '/../../rabbitMQ/get_host_info.inc');

header('Content-Type: application/json');

$action  = $_GET['action'] ?? $_POST['action'] ?? '';
$club_id = (int)($_GET['club_id'] ?? $_POST['club_id'] ?? 0);

if (!$action || !$club_id) {
  echo json_encode(['status' => 'fail', 'message' => 'Missing action or club_id']);
  exit;
}

try {
  $client = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'ClubProcessor');
  $response = [];

  switch ($action) {
    case 'list':
      $request = ['type' => 'club.events.list', 'club_id' => $club_id];
      $response = $client->send_request($request);
      
      if (($response['status'] ?? '') === 'success' && !empty($response['events'])) {
        $events = [];
        foreach ($response['events'] as $ev) {
          $events[] = [
            'id' => $ev['event_id'] ?? $ev['eventID'],
            'text' => $ev['title'],
            'start' => $ev['event_date'] . "T10:00:00", // these times arent real. should probably impleent properly later
            'end' => $ev['event_date'] . "T11:00:00",
            'description' => $ev['description'] ?? ''
          ];
        }
        echo json_encode(['status' => 'success', 'events' => $events]);
      } else {
        echo json_encode(['status' => 'fail', 'message' => 'no events found']);
      }
      break;

    case 'create':
      $title = $_POST['title'] ?? '';
      $desc  = $_POST['description'] ?? '';
      $date  = $_POST['event_date'] ?? '';

      if (!$title || !$date) {
        echo json_encode(['status' => 'fail', 'message' => 'missing title or date']);
        exit;
      }

      $request = [
        'type' => 'club.events.create',
        'club_id' => $club_id,
        'title' => $title,
        'description' => $desc,
        'event_date' => $date
      ];
      $response = $client->send_request($request);
      echo json_encode($response);
      break;

    case 'cancel':
      $event_id = $_POST['event_id'] ?? 0;
      if (!$event_id) {
        echo json_encode(['status' => 'fail', 'message' => 'missing event_id']);
        exit;
      }

      $request = ['type' => 'club.events.cancel', 'event_id' => $event_id];
      $response = $client->send_request($request);
      echo json_encode($response);
      break;
    }
  } catch (Exception $e) {
  echo json_encode(['status' => 'fail', 'message' => 'Exception: ' . $e->getMessage()]);
}
?>
