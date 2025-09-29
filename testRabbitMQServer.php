#!/usr/bin/php
<?php
require_once('rabbitMQLib.inc');

// Prefer centralized DB helper (testDB.php) if present
if (file_exists(__DIR__ . '/testDB.php')) {
  require_once __DIR__ . '/testDB.php';
}

// Define fallback get_db_connection only if not provided by an included helper
if (!function_exists('get_db_connection')) {
  function get_db_connection() {
    $mysqli = new mysqli('127.0.0.1', 'testUser', 'userPass', 'userdb');
    if ($mysqli->connect_errno) {
      error_log('DB connect failed: ' . $mysqli->connect_error);
      return null;
    }
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
  }
}

function generate_session_key($lengthBytes = 32) {
  return bin2hex(random_bytes($lengthBytes)); // 64 hex chars for 32 bytes
}

function doRegister(array $request) {
  $email = $request['email'] ?? ($request['emailAddress'] ?? '');
  $username = $request['username'] ?? ($request['uname'] ?? '');
  $password = $request['password'] ?? '';

  if ($email === '' || $username === '' || $password === '') {
    return ['status'=>'error','message'=>'Missing fields'];
  }

  $mysqli = get_db_connection();
  if (!$mysqli) return ['status'=>'error','message'=>'DB connection failed'];

  // check username
  $stmt = $mysqli->prepare('SELECT id FROM usersTable WHERE uname = ?');
  $stmt->bind_param('s', $username);
  $stmt->execute();
  $stmt->store_result();
  if ($stmt->num_rows > 0) {
    $stmt->close();
    $mysqli->close();
    return ['status'=>'error','message'=>'Username already exists'];
  }
  $stmt->close();

  $hash = password_hash($password, PASSWORD_DEFAULT);
  $ins = $mysqli->prepare('INSERT INTO usersTable (emailAddress, uname, password) VALUES (?, ?, ?)');
  if (!$ins) {
    $mysqli->close();
    return ['status'=>'error','message'=>'DB prepare failed'];
  }
  $ins->bind_param('sss', $email, $username, $hash);
  if (!$ins->execute()) {
    $ins->close();
    $mysqli->close();
    return ['status'=>'error','message'=>'DB insert failed: '.$ins->error];
  }
  $newId = $ins->insert_id;
  $ins->close();
  $mysqli->close();

  return ['status'=>'success','message'=>'User registered','user_id'=>$newId];
}

function doLogin(array $request) {
  $username = $request['username'] ?? ($request['uname'] ?? '');
  $password = $request['password'] ?? '';

  if ($username === '' || $password === '') {
    return ['status'=>'error','message'=>'Missing username or password'];
  }

  $mysqli = get_db_connection();
  if (!$mysqli) return ['status'=>'error','message'=>'DB connection failed'];

  $stmt = $mysqli->prepare('SELECT id, password FROM usersTable WHERE uname = ?');
  if (!$stmt) {
    $mysqli->close();
    return ['status'=>'error','message'=>'DB prepare failed'];
  }
  $stmt->bind_param('s', $username);
  if (!$stmt->execute()) {
    $stmt->close();
    $mysqli->close();
    return ['status'=>'error','message'=>'DB query failed'];
  }
  $stmt->bind_result($userId, $storedHash);
  if (!$stmt->fetch()) {
    $stmt->close();
    $mysqli->close();
    return ['status'=>'error','message'=>'Invalid username or password'];
  }
  $stmt->close();

  if (!password_verify($password, $storedHash)) {
    $mysqli->close();
    return ['status'=>'error','message'=>'Invalid username or password'];
  }

  $sessionKey = generate_session_key(32);
  $ins = $mysqli->prepare('INSERT INTO sessions (user_id, session_key, created_at) VALUES (?, ?, NOW())');
  if (!$ins) {
    $mysqli->close();
    return ['status'=>'error','message'=>'DB prepare failed'];
  }
  $ins->bind_param('is', $userId, $sessionKey);
  if (!$ins->execute()) {
    $ins->close();
    $mysqli->close();
    return ['status'=>'error','message'=>'Failed to create session'];
  }
  $ins->close();
  $mysqli->close();

  return ['status'=>'success','message'=>'Login successful','session_key'=>$sessionKey,'user_id'=>$userId];
}

function requestProcessor($request)
{
  if (!is_array($request)) {
    return ['status'=>'error','message'=>'Bad request'];
  }

  if (isset($request['type'])) {
    $type = $request['type'];
  } elseif (isset($request['action'])) {
    $type = $request['action'];
  } else {
    return ['status'=>'error','message'=>'Missing request type'];
  }

  switch ($type) {
    case 'register':
      return doRegister($request);
    case 'login':
      return doLogin($request);
    default:
      return ['status'=>'error','message'=>'Unsupported request type'];
  }
}

$server = new rabbitMQServer("testRabbitMQ.ini","testServer");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>

