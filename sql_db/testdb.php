<?php

function db() {
  $host = '172.28.109.213'; // need local ip, NEED TO CHANGE
  $user = 'testUser'; // needdatabase user
  $pass = '12345'; // need database password
  $name = 'testdb'; // needdatabase name

  $mysqli = new mysqli($host, $user, $pass, $name);
  if ($mysqli->connect_errno) {
    throw new RuntimeException("DB connect failed: ".$mysqli->connect_error);
  }
  return $mysqli;
}

function doRegister(array $req) {
  $email = $req['email'] ?? '';
  $username = $req['username'] ?? '';
  $password = $req['password'] ?? '';

  // validate entered fields
  if ($email === '' || $username === '' || $password === '') {
    return ['status' => 'error', 'message' => 'Missing fields'];
  }

  // hash password before saving
  $hash = password_hash($password, PASSWORD_BCRYPT);

  $conn = db();

  // check if user or email already exists
  $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR emailAddress = ?");
  $stmt->bind_param("ss", $username, $email);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
    $stmt->close();
    $conn->close();
    return ['status' => 'error', 'message' => 'User or email already exists'];
  }
  $stmt->close();

  // insert new user
  $stmt = $conn->prepare("INSERT INTO users (username, emailAddress, password_hash) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $username, $email, $hash);

  if (!$stmt->execute()) {
    $errorMsg = $stmt->error;
    $stmt->close();
    $conn->close();
    return ['status' => 'error', 'message' => 'DB insert failed: ' . $errorMsg];
  }

  $stmt->close();
  $conn->close();

  return ['status' => 'success', 'message' => 'User registered successfully'];
}



// ----------------- TEST SECTION -----------------

// Simulated registration input
$testData = [
  'email' => 'testuser@example.com',
  'username' => 'testuser1',
  'password' => 'mySecurePassword123'
];

// Call the registration function
$result = doRegister($testData);

// Print the result
echo "Registration test result:\n";
print_r($result);

/*
CREATE USER 'testUser'@'172.28.109.%' IDENTIFIED BY '12345';
GRANT ALL PRIVILEGES ON testdb.* TO 'testUser'@'172.28.109.%';
FLUSH PRIVILEGES;
*/


?>