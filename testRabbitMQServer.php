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
    $stmt = $db->prepare("SELECT username, password FROM users WHERE username = ?"); // reminder : ? is the paramter placeholder lol
    if ($stmt == false){ // DEBUGGING
        error_log("DB prepare statement for login.php failed.");
        exit(0);
    }


    $stmt->bind_param("s", $username); // referenced IT202 code for this part
    if (!$stmt->execute()){
        // IF... query failed:
        error_log("DB execute statement for login.php failed.");
        $stmt->close();
        exit;
    }
    $stmt->store_result(); // holds data

    if ($stmt->num_rows === 1) {
        // makes sure to limit matching results/rows to ONE only
        $stmt->bind_result($id, $dbUser, $dbHash);
        $stmt->fetch(); // fetch the row into $id, $dbUser, $dbHash
        // idk if we need id???

        // verify submitted password against hashed password from DB
        if (password_verify($password, $dbHash)) {
            $_SESSION['login'] = true;
            $_SESSION['username'] = $dbUser;
            $_SESSION['userID']   = $userid;

            echo json_encode(['status' => 'success', 'message' => 'Login successful']);
        } 
        else {
            // wrong password
            echo json_encode(['status' => 'fail', 'message' => 'Invalid credentials']);
        }
    } else {
        // no user found
        echo json_encode(['status' => 'fail', 'message' => 'Invalid credentials']);
    }
    
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



/*
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

*/

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

