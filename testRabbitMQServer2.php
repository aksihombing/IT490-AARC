 #!/usr/bin/php
<?php
require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/testDB.php'; 


// 90-second token lifetime  10/9
const SESSION_LIFETIME_SECONDS = 90;


function generate_session_key($lengthBytes = 32) {
  return bin2hex(random_bytes($lengthBytes));
}
 
// will generate a random session key and store it in sessions table

function doRegister(array $request) {
  // choose one set of names, or keep both if you prefer tolerance
  $email    = $request['email']    ?? ($request['emailAddress'] ?? '');
  $username = $request['username'] ?? ($request['uname'] ?? '');
  $password = $request['password'] ?? '';

  if ($email === '' || $username === '' || $password === '') {
    return ['status'=>'error','message'=>'Missing fields'];
  }

  $mysqli = testdb();
  if (!$mysqli) return ['status'=>'error','message'=>'DB connection failed'];

  // Makes sure that users dont register with the same username, so it will return an error if they do
  $stmt = $mysqli->prepare('SELECT id FROM usersTable WHERE uname = ?');
  if (!$stmt) { $mysqli->close(); return ['status'=>'error','message'=>'DB prepare failed']; }
  $stmt->bind_param('s', $username);
  if (!$stmt->execute()) { $stmt->close(); $mysqli->close(); return ['status'=>'error','message'=>'DB query failed']; }
  $stmt->store_result();
  if ($stmt->num_rows > 0) { $stmt->close(); $mysqli->close(); return ['status'=>'error','message'=>'Username already exists']; }
  $stmt->close();

  // this is where the password is hashed and then stored in the database, then this stored hash is used to verify the password for logging in
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $ins  = $mysqli->prepare('INSERT INTO usersTable (emailAddress, uname, password) VALUES (?, ?, ?)');
  if (!$ins) { $mysqli->close(); return ['status'=>'error','message'=>'DB prepare failed']; }
  $ins->bind_param('sss', $email, $username, $hash);
  if (!$ins->execute()) { $ins->close(); $mysqli->close(); return ['status'=>'error','message'=>'DB insert failed: '.$ins->error]; }
  $newId = $ins->insert_id;
  $ins->close();
  $mysqli->close();

  return ['status'=>'success','message'=>'User registered','user_id'=>$newId];
}

// this function handles login request by verifying the username and password 
function doLogin(array $request) {
  $username = $request['username'] ?? ($request['uname'] ?? '');
  $password = $request['password'] ?? '';

  if ($username === '' || $password === '') {
    return ['status'=>'error','message'=>'Missing username or password'];
  }

  $mysqli = testdb();
  if (!$mysqli) return ['status'=>'error','message'=>'DB connection failed'];

  // fetch stored hash and user id in order to verify the password and then crete a sesion key
  $stmt = $mysqli->prepare('SELECT id, password FROM usersTable WHERE uname = ?');
  if (!$stmt) { $mysqli->close(); return ['status'=>'error','message'=>'DB prepare failed']; }
  $stmt->bind_param('s', $username);
  if (!$stmt->execute()) { $stmt->close(); $mysqli->close(); return ['status'=>'error','message'=>'DB query failed']; }
  $stmt->bind_result($userId, $storedHash);
  if (!$stmt->fetch()) { $stmt->close(); $mysqli->close(); return ['status'=>'error','message'=>'Invalid username or password']; }
  $stmt->close();

  // this is the part where the password is actually verified with its stored hash from the database
  // if it does not match then it will return an error message
  if (!password_verify($password, $storedHash)) {
    $mysqli->close();
    return ['status'=>'error','message'=>'Invalid username or password'];
  }

  // This is the part where the session key is created and stored in the sessions table
  $sessionKey = generate_session_key(32);
  $ins = $mysqli->prepare(
  'INSERT INTO sessions (user_id, session_key, created_at, expires_at)
   VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND))'// 10/9 This is new and here the SQL query is setting the expiration time for ecery session. Records currnt time and then the session lifetime on top of it.
);
if (!$ins) { $mysqli->close(); return ['status'=>'error','message'=>'DB prepare failed']; }

$life = SESSION_LIFETIME_SECONDS;
$ins->bind_param('isi', $userId, $sessionKey, $life);// 10/9 binding the session lifetime to the SQL query

if (!$ins->execute()) {
  $ins->close(); $mysqli->close();
  return ['status'=>'error','message'=>'Failed to create session'];
}
$ins->close();
$mysqli->close();

  return ['status'=>'success','message'=>'Login successful','session_key'=>$sessionKey,'user_id'=>$userId];
}


function doValidate(array $request) {
  $sessionKey = $request['session_key'] ?? '';
  if ($sessionKey === '') return ['status'=>'error','message'=>'Missing session_key'];

  $mysqli = testdb();
  if (!$mysqli) return ['status'=>'error','message'=>'DB connection failed'];

  // Only valid if not expired
  $sql = 'SELECT user_id FROM sessions WHERE session_key = ? AND NOW() < expires_at';// 10/9 This is new and here the SQL query checks if the session is expired or not
  $stmt = $mysqli->prepare($sql);
  if (!$stmt) { $mysqli->close(); return ['status'=>'error','message'=>'DB prepare failed']; }// 10/9 it looks the session key up in the sessions table and also checks if the current time is less than the expiration time

  $stmt->bind_param('s', $sessionKey);
  $stmt->execute();
  $stmt->bind_result($uid);

  if ($stmt->fetch()) {
    $stmt->close(); $mysqli->close();
    return ['status'=>'success','user_id'=>$uid];
  }
  $stmt->close(); $mysqli->close();
  return ['status'=>'fail','message'=>'Session expired or not found'];
}


function doLogout(array $request) {
  $sessionKey = $request['session_key'] ?? '';
  if ($sessionKey === '') return ['status'=>'error','message'=>'Missing session_key'];

} //Just delete the session key from the sessions table to log out
 
//here the request processor that will call the appropriate function based on the request type
// for example, if the request type is 'login', it will call doLogin function
// if the request type is 'register', it will call doRegister function    
//this is the first function that gets called when the server receives a request
function requestProcessor($request)
{
  if (!is_array($request)) {
    return ['status'=>'error','message'=>'Bad request'];
  }

  $type = $request['type'] ?? $request['action'] ?? null;
  if ($type === null) {
    return ['status'=>'error','message'=>'Missing request type'];
  }
/*
 switch ($type) {
  case 'register': return doRegister($request);
  case 'login':    return doLogin($request);
  case 'validate': return doValidate($request);
  case 'logout':   return doLogout($request);
  default:         return ['status'=>'error','message'=>'Unsupported request type'];
}
*/ //We have four queues now so we we dont really need the switch statememnts anymore, but ill leave it here for refrence

}
// here the server is started and it will process requests using the requestProcessor function
// this is the firsyt thing that gets executed when the server is run
$server = new rabbitMQServer("testRabbitMQ.ini","testServer");
echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');  // blocks and serves forever
echo "testRabbitMQServer END".PHP_EOL;
exit();

 
 
 // self-reminder  there is a seed user or like the example user (not sure what to call it) in the userdb.sql that might not be needed or work with registration
   //because registration takes the users plaintext  and then hashes it and the stores the hash in the password in the user table 
   //so the login checks the plaintext against the hash in the db and if it matches then it logs in
   //but the seed user has a plaintext password so it will not work with login
   // so we might have to take it out i believe, 
