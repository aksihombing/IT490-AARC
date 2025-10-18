<?php

require_once 'mysqlconnect.php';
    

$result = $db->query('
    SELECT 
        eventID,
        title,
        location,
        startTime,
        endTime 
    FROM events
');

class Event {}
$events = array();

foreach($result as $row) {
    $e = new Event();
    $e->id = $row['eventID'];     
    $e->text = $row['title'];     
    $e->start = $row['startTime'];  
    $e->end = $row['endTime'];      
    $e->resource = $row['location']; 
    $events[] = $e;
}

header('Content-Type: application/json');

echo json_encode($events);

?>
