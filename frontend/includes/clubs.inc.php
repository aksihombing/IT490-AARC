<?php
require_once(__DIR__ . '/../../rabbitMQ/rabbitMQLib.inc');
require_once(__DIR__ . '/../../rabbitMQ/log_producer.php');

//session should be started already??
$userId = $_SESSION['user_id'] ?? 0;

$clubs = [];
$events = [];
$message = '';


//handles all the form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    try{

        $action = $_POST['action'] ?? '';

        //create club form logic
        if ($action === 'create_club'){
            $client = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'ClubProcessor');
            $res = $client->send_request([
                'type' => 'club.create',
                'action' => 'create.club',
                'user_id' => $userId,
                'club_name' => $_POST['club_name'] ?? '',
                'description' => $_POST['description'] ?? ''
            ]);

            $message = $res['message'] ?? 'club created';
        }


        // create event logic
        if ($action === 'create_event'){
            $client = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'ClubProcessor');
            $res = $client->send_request([
                'type' => 'club.create',
                'action' => 'create.event',
                'user_id' => $userId,
                'club_id' => $_POST['club_id'],
                'title' => $_POST['title'],
                'event_date' => $_POST['event_date'],
                'description' => $_POST['description'] ?? ''
            ]);

            $message = $res['message'] ?? 'event created';
        }

        //event rsvp logic
        if ($action === 'rsvp') {
            $client = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'ClubProcessor');
            $res = $client->send_request([
                'type' => 'club.create',
                'action' => 'create.rsvp',
                'event_id' => $_POST['event_id'],
                'user_id' => $userId,
                'status' => $_POST['status']
            ]);

            $message = $res['message'] ?? 'rsvp confirmed';
        }

    } catch (Exception $e){
        $error = $e->getMessage();
        log_event("frontend", "error", $error);
    }
}


//logic for leading the club list
try{
    $client = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'ClubProcessor');
    $res = $client->send_request([
        'type' => 'club.create',
        'action' => 'create.list',
        'user_id' => $userId
    ]);

    if ($res['status'] === 'success') {
        $clubs = $res['clubs'];
    }
} catch (Exception $e){
    $error = $e->getMessage();
    log_event("frontend","error",$error);
}



//loading rsvp list (events booked)
try{
    $client = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'ClubProcessor');
    $res = $client->send_request([
        'type' => 'club.create',
        'action' => 'create.event.list',
        'user_id' => $userId
    ]);

    if ($res['status'] === 'success') {
        $events = $res['events'];
    }
} catch (Exception $e){
    $error = $e->getMessage();
    log_event("frontend","error",$error);
}
