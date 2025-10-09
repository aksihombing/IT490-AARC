#!/usr/bin/php
<?php

// rabbitMQServer should run continuously on the DATABASE VM to grab requests from queue
// it listens for messages, takes from the queue, processes it to the DB, and sends response if needed.

require_once('rabbitMQLib.inc'); // also references get_host_info.inc

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
function doRegister($username, $password, $email){
  require_once('../sql_db/db_functions.php');
  $conn = getDBConnection();
  if (!$conn){
    return ['status' => 'error', 'message' => 'Failed DB connection.'];
  }
  // NOTE : password is already hashed in register.php


  // checks if username exists before registering
  $stmt = $conn->prepare("SELECT * FROM users (username = ?");
  $stmt->bind_param("s",$username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows >0){
    return ['status'=>'fail', 'message' => 'Username already exists'];
  }

  
  // insert NEW user if there was no existing name yet
  $stmt = $conn->prepare("INSERT INTO users (username, email, password VALUES ?");
  $stmt->bind_param("sss", $username, $password, $email);
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

$server = new rabbitMQServer("host.ini","testServer");
echo "RabbitMQ Server BEGIN".PHP_EOL;
$server->process_requests('requestProcessor'); // PROCESSES REQUESTS UNTIL THERE ARE NONE !!
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>
