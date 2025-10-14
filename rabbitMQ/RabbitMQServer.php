#!/usr/bin/php
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);

// rmq script to stay running and listen for messages. db listener
// when it receives a message, should talk to sql and send back a result

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


// request handlers
function doRegister(array $req) {
  $email = $req['email'] ?? '';
  $username = $req['username'] ?? '';
  $hash = $req['password'] ?? '';

// validate entered fields
  if ($email==='' || $username==='' || $hash==='') {
    return ['status'=>'fail','message'=>'missing fields'];
  }

  $conn = db();

// see if user already exists in db
  $stmt = $conn->prepare("SELECT id FROM users WHERE username=? OR emailAddress=?");
  $stmt->bind_param("ss", $username, $email);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
    return ['status'=>'fail','message'=>'user or email exists'];
  }
  $stmt->close();

// inserts new user into database
  $stmt = $conn->prepare("INSERT INTO users (username,emailAddress,password_hash) VALUES (?,?,?)");
  $stmt->bind_param("sss", $username, $email, $hash);
  if (!$stmt->execute()) {
    return ['status'=>'fail','message'=>'db insert failed'];
  }

  return ['status'=>'success'];
}

function doLogin(array $req) {
  $username = $req['username'] ?? '';
  $password = $req['password'] ?? '';

 if ($username==='' || $password==='') {
    return ['status'=>'fail','message'=>'missing fields'];
  }
  $conn = db();


  $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows === 1) { 
    // checks if theres a row in the db with from the query result
    $stmt->bind_result($uid,$dbUser,$dbHash);
    $stmt->fetch();
    if (password_verify($password,$dbHash)){
      return ['status'=>'success','uid'=>$uid,'username'=>$dbUser];
    }
    else { return ['status'=>'fail', 'message' => 'Invalid password']; }
  }
  else { return ['status'=>'fail', 'message' => 'User not found']; }

  // create a session key, should be secure ?
  $session = bin2hex(random_bytes(32));
  $exp = (new DateTime('+7 days'))->format('Y-m-d H:i:s');

  // stores the session in the db
  $stmt = $conn->prepare("INSERT INTO sessions (user_id, session_key, expires_at) VALUES (?,?,?)");
  $stmt->bind_param("iss", $uid, $session, $exp);
  $stmt->execute();

  return ['status'=>'success', 'uid' => $uid,'session_key'=>$session];
}

function doValidate(array $req) {
  $sid = $req['session_key'] ?? '';
  if ($sid==='') return ['status'=>'fail','message'=>'missing session'];

  $conn = db();
  $stmt = $conn->prepare("
      SELECT u.id,u.username,u.email,s.expires_at
      FROM sessions s
      JOIN users u ON u.id=s.user_id
      WHERE s.session_key=? LIMIT 1
  ");
  $stmt->bind_param("s", $sid);
  $stmt->execute();
  $stmt->bind_result($uid,$uname,$email,$exp);

  if (!$stmt->fetch()) return ['status'=>'fail','message'=>'not found'];
  if ($exp && strtotime($exp) < time()) {
    // session is expired so deletes
    $del = $conn->prepare("DELETE FROM sessions WHERE session_key=?");
    $del->bind_param("s",$sid);
    $del->execute();
    return ['status'=>'fail','message'=>'expired'];
  }

  return ['status'=>'success','user'=>['id'=>$uid,'username'=>$uname,'email'=>$email]];
}

function doLogout(array $req) {
  $sid = $req['session_key'] ?? '';
  if ($sid==='') return ['status'=>'fail','message'=>'missing session'];

  $conn = db();
  $stmt = $conn->prepare("DELETE FROM sessions WHERE session_key=?");
  $stmt->bind_param("s",$sid);
  $stmt->execute();
  return ['status'=>'success'];
}

// decides which function to run
function requestProcessor($req) {
  echo "Received request:\n";
    var_dump($req);
    flush();
  
  if (!isset($req['type'])) {
    return ['status'=>'fail','message'=>'no type'];
  }

  switch ($req['type']) {
    case 'register': return doRegister($req);
    case 'login':    return doLogin($req);
    case 'validate': return doValidate($req);
    case 'logout':   return doLogout($req);
    default:         return ['status'=>'fail','message'=>'unknown type'];
  }
}

echo "Auth server ready, waiting for requests\n";
flush();

// single queue version test
$which = $argv[1] ?? 'AuthRegister';
echo "Auth server starting for queue section: {$which}\n";
$server = new rabbitMQServer(__DIR__ . "/host.ini", $which);
echo "Connecting to queue: {$which}\n";
flush();
$server->process_requests('requestProcessor');
echo "Auth server stopped for {$which}\n";
