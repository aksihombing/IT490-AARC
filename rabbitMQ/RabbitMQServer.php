#!/usr/bin/php
<?php
// rmq script to stay running and listen for messages. listener
// when it receives a message, should talk to sql and send back a result

require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/get_host_info.inc';

// connects to the local sql database
function db()
{
  $host = '172.28.109.126'; // need local ip, NEED TO CHANGE
  $user = 'testUser'; // needdatabase user
  $pass = '12345'; // need database password
  $name = 'testdb'; // needdatabase name

  $mysqli = new mysqli($host, $user, $pass, $name);
  if ($mysqli->connect_errno) {
    throw new RuntimeException("DB connect failed: " . $mysqli->connect_error);
  }
  return $mysqli;
}


// request handlers
function doRegister(array $req)
{
  $email = $req['email'] ?? '';
  $username = $req['username'] ?? '';
  $hash = $req['password'] ?? '';

  // validate entered fields
  if ($email === '' || $username === '' || $hash === '') {
    return ['status' => 'fail', 'message' => 'missing fields'];
  }

  $conn = db();

  // see if user already exists in db
  $stmt = $conn->prepare("SELECT id FROM users WHERE username=? OR emailAddress=?");
  $stmt->bind_param("ss", $username, $email);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
    return ['status' => 'fail', 'message' => 'user or email exists'];
  }
  $stmt->close();

  // inserts new user into database
  $stmt = $conn->prepare("INSERT INTO users (username,emailAddress,password_hash) VALUES (?,?,?)");
  $stmt->bind_param("sss", $username, $email, $hash);
  if (!$stmt->execute()) {
    return ['status' => 'fail', 'message' => 'db insert failed'];
  }

  return ['status' => 'success'];
}

function doLogin(array $req)
{
  $username = $req['username'] ?? '';
  $password = $req['password'] ?? '';

  if ($username === '' || $password === '') {
    return ['status' => 'fail', 'message' => 'missing fields'];
  }
  $conn = db();


  $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows === 1) { // triple equal is stricter than ==
    // checks if theres a row in the db with from the query result
    $stmt->bind_result($uid, $dbUser, $dbHash);
    $stmt->fetch();
    if (password_verify($password, $dbHash)) {
      return ['status' => 'success', 'uid' => $uid, 'username' => $dbUser];
    } else {
      return ['status' => 'fail', 'message' => 'Invalid password'];
    }
  } else {
    return ['status' => 'fail', 'message' => 'User not found'];
  }

  // create a session key, should be secure ?
  $session = bin2hex(random_bytes(32));
  $exp = (new DateTime('+7 days'))->format('Y-m-d H:i:s');

  // stores the session in the db
  $stmt = $conn->prepare("INSERT INTO sessions (user_id, session_key, expires_at) VALUES (?,?,?)");
  $stmt->bind_param("iss", $uid, $session, $exp);
  $stmt->execute();

  return ['status' => 'success', 'session_key' => $session];
}

function doValidate(array $req)
{
  $sid = $req['session_key'] ?? '';
  if ($sid === '') return ['status' => 'fail', 'message' => 'missing session'];

  $conn = db();
  $stmt = $conn->prepare("
      SELECT u.id,u.username,u.email,s.expires_at
      FROM sessions s
      JOIN users u ON u.id=s.user_id
      WHERE s.session_key=? LIMIT 1
  ");
  $stmt->bind_param("s", $sid);
  $stmt->execute();
  $stmt->bind_result($uid, $uname, $email, $exp);

  if (!$stmt->fetch()) return ['status' => 'fail', 'message' => 'not found'];
  if ($exp && strtotime($exp) < time()) {
    // session is expired so deletes
    $del = $conn->prepare("DELETE FROM sessions WHERE session_key=?");
    $del->bind_param("s", $sid);
    $del->execute();
    return ['status' => 'fail', 'message' => 'expired'];
  }

  return ['status' => 'success', 'user' => ['id' => $uid, 'username' => $uname, 'email' => $email]];
}

function doLogout(array $req)
{
  $sid = $req['session_key'] ?? '';
  if ($sid === '') return ['status' => 'fail', 'message' => 'missing session'];

  $conn = db();
  $stmt = $conn->prepare("DELETE FROM sessions WHERE session_key=?");
  $stmt->bind_param("s", $sid);
  $stmt->execute();
  return ['status' => 'success'];
}

// decides which function to run
function requestProcessor($req)
{
  if (!isset($req['type'])) {
    return ['status' => 'fail', 'message' => 'no type'];
  }

  switch ($req['type']) {
    case 'register':
      return doRegister($req);
    case 'login':
      return doLogin($req);
    case 'validate':
      return doValidate($req);
    case 'logout':
      return doLogout($req);
    default:
      return ['status' => 'fail', 'message' => 'unknown type'];
  }
}

// server logic

echo "Auth server starting…\n";

// creates a server per each queue section in the host.ini
$servers = [
  new rabbitMQServer(__DIR__ . "/host.ini", "AuthRegister"),
  new rabbitMQServer(__DIR__ . "/host.ini", "AuthLogin"),
  new rabbitMQServer(__DIR__ . "/host.ini", "AuthValidate"),
  new rabbitMQServer(__DIR__ . "/host.ini", "AuthLogout"),
];

// child process for each queue so they can listen at the same time
pcntl_async_signals(true);
$children = [];

foreach ($servers as $srv) {
  $pid = pcntl_fork();

  // child process runs the server
  if ($pid === 0) {
    $srv->process_requests('requestProcessor');
    exit(0);
  }

  $children[] = $pid;
}

echo "Auth server running (" . count($children) . " workers)…\n";

// parent process just waits forever so children stay alive
while (true) {
  sleep(5);
}
