
<html>

<?php
session_start();
//require_once(__DIR__.'/../rabbitMQ/RabbitMQServer.php');
//require_once(__DIR__.'/../rabbitMQ/rabbitMQLib.inc');

$sessionKey = $_SESSION['session_key'] ?? null; // check for session key

// use RabbitMQ client
$client = new rabbitMQClient("host.ini", "AuthValidate");
$userData = null;

if ($sessionKey){
  $response = $client->send_request([
    'type' => 'validate',
    'session_key' => $sessionKey
  ]);
  if($response['status']==='success'){
    $userData = $response['user'];
  }
  else {
    // invalid session or expired.
    unset($_SESSION['session_key']);
  }

}



// NO VALID SESSION : 
if (!isset($_SESSION['session_key']) || !$userData) {
?>
<head>
  <title>AARC Page</title>
  <!-- <link rel="stylesheet" type="text/css" href="ih_styles.css"> -->
</head>

<body>
<!-- <header style="height:15%;">
    <?php //include("header.inc.php");?>
</header> -->
  <section>
  <!-- insert NAV here -->
   <main>
    <?php
    // PAGE CONTENT REDIRECTION
        if (isset($_REQUEST['content'])) {
            include($_REQUEST['content'] . ".inc.php");
        } else {
            include("main.inc.php");
        }
      }
        ?>
  else { // Session IS valid!


  }
   </main>

  </section>


</body>

<footer> <?php include("footer.inc.php"); ?> </footer>


</html>
