<?php
function db() {
  $host = '172.28.109.126
  '; // need local ip, NEED TO CHANGE
  $user = 'testUser'; // needdatabase user
  $pass = ''; // need database password
  $name = 'testdb'; // needdatabase name

  $mysqli = new mysqli($host, $user, $pass, $name);
  if ($mysqli->connect_errno) {
    throw new RuntimeException("DB connect failed: ".$mysqli->connect_error);
  }
  return $mysqli;
}



function doLogin(array $req) {
  $username = $req['username'] ?? '';
  $password = $req['password'] ?? '';
  $conn = db();

  if ($username==='' || $password==='') {
    return ['status'=>'fail','message'=>'missing fields'];
  }

  $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows === 1) { // triple equal is stricter than ==
    // checks if theres a row in the db with from the query result
    $stmt->bind_result($id,$dbUser,$dbHash);
    $stmt->fetch();
    if (password_verify($password,$dbHash)){
      return ['status'=>'success','uid'=>$id,'username'=>$dbUser];
    }
    else { return ['status'=>'fail', 'message' => 'Invalid password']; }
  }
  else { return ['status'=>'fail', 'message' => 'User not found']; }

/*
  $conn = db();
  $stmt = $conn->prepare("SELECT id,password_hash, emailAddress FROM users WHERE username=? LIMIT 1");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $stmt->bind_result($uid,$hash,$email);

  // check the login credentials
  if (!$stmt->fetch() || !password_verify($password,$hash)) {
    return ['status'=>'fail','message'=>'invalid credentials'];
  }*/

  // create a session key, should be secure ?
  $session = bin2hex(random_bytes(32));
  $exp = (new DateTime('+7 days'))->format('Y-m-d H:i:s');

  // stores the session in the db
  $stmt = $conn->prepare("INSERT INTO sessions (user_id, session_key, expires_at) VALUES (?,?,?)");
  $stmt->bind_param("iss", $uid, $session, $exp);
  $stmt->execute();

  return ['status'=>'success','session_key'=>$session];
}

// ----------------- TEST SECTION -----------------

// Simulated login input
$testData = [
  'username' => 'testuser1',
  'password' => 'mySecurePassword123'
];

// Call the registration function
$result = doLogin($testData);

// Print the result
echo "Login test result:\n";
print_r($result);


?>