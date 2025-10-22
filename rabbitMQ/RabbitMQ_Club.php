#!/usr/bin/php
<?php
// db listener for club + event features

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/get_host_info.inc';

// connects to the local sql database
function db() {
  $host = 'localhost'; 
  $user = 'testUser'; 
  $pass = '12345';
  $name = 'testdb'; 

  $mysqli = new mysqli($host, $user, $pass, $name);
  if ($mysqli->connect_errno) {
    throw new RuntimeException("DB connect failed: ".$mysqli->connect_error);
  }
  return $mysqli;
}

// ---- feature 1: create club ----- 
function doCreateClub(array $req) {
  $owner_id = $req['user_id'] ?? 0;
  $name = $req['club_name'] ?? '';
  $desc = $req['description'] ?? '';

  if (!$owner_id || $name === '') {
    return ['status' => 'fail', 'message' => 'form is missing required fields'];
  }

  $conn = db();
  $stmt = $conn->prepare("INSERT INTO clubs (owner_id, name, description) VALUES (?, ?, ?)");
  $stmt->bind_param("iss", $owner_id, $name, $desc);
  if (!$stmt->execute()) {
    return ['status' => 'fail', 'message' => 'database insert failed: ' . $stmt->error];
  }

  return ['status' => 'success', 'club_id' => $conn->insert_id];
}


// ---- feature 2: invite member to club ----- 

function doInviteMember(array $req) {
  $club_id = $req['club_id'] ?? 0;
  $user_id = $req['user_id'] ?? 0;

  if (!$club_id || !$user_id) {
    return ['status' => 'fail', 'message' => 'missing parameters'];
  }

  $conn = db();
  // avoids duplicate club members hopefully. similar to our register logic
  $check = $conn->prepare("SELECT member_id FROM club_members WHERE club_id=? AND user_id=?");
  $check->bind_param("ii", $club_id, $user_id);
  $check->execute();
  $check->store_result();

  if ($check->num_rows > 0) {
    return ['status' => 'fail', 'message' => 'user is already a member'];
  }

  // inserts user into club db ""
  $stmt = $conn->prepare("INSERT INTO club_members (club_id, user_id) VALUES (?, ?)");
  $stmt->bind_param("ii", $club_id, $user_id);
  if (!$stmt->execute()) {
    return ['status' => 'fail', 'message' => $stmt->error];
  }

  return ['status' => 'success', 'message' => 'member invited'];
}


// ---- feature 3: create club event ----- 

function doCreateEvent(array $req) {
  $club_id = $req['club_id'] ?? 0;
  $title = $req['title'] ?? '';
  $date = $req['event_date'] ?? null;
  $desc = $req['description'] ?? '';

  if (!$club_id || $title === '') {
    return ['status' => 'fail', 'message' => 'form is missing required fields'];
  }

  $conn = db();
  $stmt = $conn->prepare("INSERT INTO club_events (club_id, title, event_date, description) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("isss", $club_id, $title, $date, $desc);
  if (!$stmt->execute()) {
    return ['status' => 'fail', 'message' => $stmt->error];
  }

  return ['status' => 'success', 'event_id' => $conn->insert_id];
}


// ---- feature 4: list club events ----- 

function doListEvents(array $req) {
  $club_id = $req['club_id'] ?? 0;
  if (!$club_id) return ['status' => 'fail', 'message' => 'missing club_id'];

  $conn = db();
  $stmt = $conn->prepare("SELECT event_id, title, event_date, description FROM club_events WHERE club_id=? ORDER BY event_date ASC");
  $stmt->bind_param("i", $club_id);
  $stmt->execute();
  $result = $stmt->get_result();

  $events = [];
  while ($row = $result->fetch_assoc()) {
    $events[] = $row;
  }

  return ['status' => 'success', 'events' => $events];
}


// ---- feature 5: cancel club event ----- 

function doCancelEvent(array $req) {
  $event_id = $req['event_id'] ?? 0;
  if (!$event_id) return ['status' => 'fail', 'message' => 'missing event_id'];

  $conn = db();
  $stmt = $conn->prepare("DELETE FROM club_events WHERE event_id=?");
  $stmt->bind_param("i", $event_id);
  if (!$stmt->execute()) {
    return ['status' => 'fail', 'message' => $stmt->error];
  }

  return ['status' => 'success', 'message' => 'event cancelled'];
}

// ---- feature 6: list clubs -----
function doList(array $req) {
  $user_id = $req['user_id'] ?? 0;
  if (!$user_id) return ['status' => 'fail', 'message' => 'missing user_id'];

  $conn = db();
  // user is owner or member of club
  $stmt = $conn->prepare("
    SELECT DISTINCT c.club_id, c.name, c.description, c.owner_id
    FROM clubs c
    LEFT JOIN club_members m ON c.club_id = m.club_id
    WHERE c.owner_id = ? OR m.user_id = ?
    ORDER BY c.name ASC
  ");
  $stmt->bind_param("ii", $user_id, $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  $clubs = [];
  while ($row = $result->fetch_assoc()) {
    $clubs[] = $row;
  }
  $stmt->close();
  $conn->close();

  return ['status' => 'success', 'clubs' => $clubs];
}


// decides which function to run
function requestProcessor($req) {
  echo "Received club request:\n";
    var_dump($req);
    flush();
  
  if (!isset($req['type'])) {
    return ['status'=>'fail','message'=>'no type'];
  }

  switch ($req['type'] ?? '') {
    case 'club.create': return doCreateClub($req);
    case 'club.invite': return doInviteMember($req);
    case 'club.events.create': return doCreateEvent($req);
    case 'club.events.cancel': return doCancelEvent($req);
    case 'club.list': return doList($req);
    case 'club.events.list': return doListEvents($req);
    default: return ['status' => 'fail', 'message' => 'unknown type'];
  }
}

echo "Book Club server starting...\n";
$iniPath = __DIR__ . '/host.ini';
$server = new rabbitMQServer($iniPath, 'ClubProcessor');
$server->process_requests('requestProcessor');

?>