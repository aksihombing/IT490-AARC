<?php

$db_exists = file_exists("daypilot.sqlite");

$db = new PDO('sqlite:daypilot.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!$db_exists) {
  //create the database
  $db->exec("CREATE TABLE IF NOT EXISTS events (
                        id INTEGER PRIMARY KEY, 
                        name TEXT, 
                        start DATETIME, 
                        end DATETIME,
                        color VARCHAR(30))");

  $items = array(
      array('name' => 'Event 1',
          'start' => '2021-08-09T10:00:00',
          'end' => '2021-08-09T12:00:00',
          'color' => '')
  );

  $insert = "INSERT INTO events (name, start, end, color) VALUES (:name, :start, :end, :color)";
  $stmt = $db->prepare($insert);

  $stmt->bindParam(':name', $name);
  $stmt->bindParam(':start', $start);
  $stmt->bindParam(':end', $end);
  $stmt->bindParam(':color', $color);

  foreach ($items as $it) {
    $name = $it['name'];
    $start = $it['start'];
    $end = $it['end'];
    $color = $it['color'];
    $stmt->execute();
  }

}
