#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');


// LOGIN FUNCTION
function doLogin($username,$password)
{
    // lookup username in databas
    // check password
    $login = new loginDB(); // what is loginDB?
    return $login->validateLogin($username,$password);
    //return true;
    //return false if not valid
}

// REGISTER FUNCTION
function doRegister($email,$username,$password)
{
  $login = new loginDB(); // what db do we need?
  return $login->validateLogin($username,$password);
}




// VALIDATE SESSION FUNCTION
function doValidate($_SESSION) // idk how to fix this ??
{
  if (!isset($_SESSION['sessionId'])){
    echo "Invalid session";
  }
  else{
    echo "Session is valid";
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
    case "validate_session":
      return doValidate($request['sessionId']);
      
  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}





$server = new rabbitMQServer("testRabbitMQ.ini","testServer");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>

