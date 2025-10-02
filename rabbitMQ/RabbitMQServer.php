#!/usr/bin/php
<?php
// USE THIS INSTEAD OF testRabbitMQServer

// require_once('login.php.inc'); // we dont have this yet
require_once('rabbitMQLib.inc'); // also references get_host_info.inc

$db = new mysqli('172.28.172.114','testUser','12345','testdb');


// LOGIN FUNCTION
function doLogin($username, $password){
  global $db;

  $stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = ?");
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

// LOGIN FUNCTION
function doRegister($username, $password, $email){
  global $db;

  // NOTE : password is already hashed in register.php

  $stmt = $db->prepare("INSERT INTO users (username, email, password VALUES ?");
  if ($stmt == false){
    error_log("DB couldn't perform doRegister:".$db->error); // debugging error
    return ['status'=>'fail', 'message' => 'Database error'];
  }
  // if success:
  $stmt->bind_param("sss", $username, $password, $email);
  if ($stmt->execute()){
    return [
      'status' => 'success',
      'message' => 'User registered successfully',
      'status' => $stmt->insert_id // auto-ID from mysql
    ];
  }
  else {
    error_log("DB couldn't insert data from doRegister:".$db->error); // debugging error
    return ['status'=>'fail', 'message' => 'Database insert fail'];
  }
}


// ------------- PROCESS DATA ------------
function requestProcessor($request)
{
  echo "received request".PHP_EOL;
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
      
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}



// ACTUAL SERVER PROCESSING

$server = new rabbitMQServer("host.ini","testServer");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>

