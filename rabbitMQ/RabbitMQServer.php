#!/usr/bin/php
<?php

// rabbitMQServer should run continuously on the Database VM's machine
// DB runs file > connects to RabbitMQ > Listens for requests and processes


require_once(__DIR__.'/rabbitMQLib.inc'); // also references get_host_info.inc
require_once(__DIR__.'/get_host_info.inc');
//require_once(__DIR__ . '/../sql_db/db_functions.php'); //already within doLogin and doRegister

// ------------- PROCESS REQUESTS ------------
function requestProcessor($request)
{
  echo "Received request".PHP_EOL;
  var_dump($request);
  if(!isset($request['type']))
  {
    return "ERROR: unsupported message type";
  }
  switch ($request['type'])
  {
    case "register":
      return doRegister($request['email'],$request['username'],$request['password']);
    case "login":
      return doLogin($request['username'],$request['password']);
   // case "validate_session":
      //return doValidate($request['sessionId']);
    default:
        return ['status' => 'error', 'message' => 'Invalid request'];
      
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed"); // good for debugging, but can be removed if unnecessary
}


// REGISTER FUNCTION
function doRegister($email, $username, $password){
  require_once('../sql_db/db_functions.php');
  $conn = getDBConnection();
  if (!$conn){
    return ['status' => 'error', 'message' => 'Failed DB connection.'];
  }
  // NOTE : password is already hashed in register.php


  // checks if username exists before registering
  $stmt = $conn->prepare("SELECT * FROM users WHERE (username = ?)");
  $stmt->bind_param("s",$username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows >0){
    return ['status'=>'fail', 'message' => 'Username already exists'];
  }

  
  // insert NEW user if there was no existing name yet
  $stmt = $conn->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $email, $username, $password);
  if ($stmt->execute()){
    return [
      'status' => 'success',
      'message' => 'User registered successfully',
      //'status' => $stmt->insert_id // auto-ID from mysql
    ];
  }
  else {
    error_log("DB couldn't insert data from doRegister:".$conn->error); // debugging error
    return ['status'=>'fail', 'message' => 'Database insert fail'];
  }
}



// LOGIN FUNCTION
function doLogin($username, $password){
  require_once('../sql_db/db_functions.php');
  $conn = getDBConnection();

  if (!$conn){
    return ['status' => 'error', 'message' => 'Failed DB connection.'];
  }

  $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
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

}


// MAIN SERVER LOOP --------------------
echo "[*] RabbitMQ Server starting...".PHP_EOL;

$server = new rabbitMQServer("host.ini","testServer");

if (!$server){
  echo "[!] ERROR: RabbitMQServer could not connect";
  exit; // ends server if not able to connect
}

$server->process_requests('requestProcessor'); // PROCESSES REQUESTS UNTIL THERE ARE NONE !!
echo "[x] RabbitMQ Server shutting down".PHP_EOL;
exit();
?>
