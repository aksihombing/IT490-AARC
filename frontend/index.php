<?php
session_start();
require_once(__DIR__ . '/../rabbitMQ/rabbitMQLib.inc');


$client = new rabbitMQClient(__DIR__ . "/../host.ini", "AuthValidate");

// check for existing session key
$sessionKey = $_SESSION['session_key'] ?? null;
$userData = null;

if ($sessionKey) {
  $response = $client->send_request([
    'type' => 'validate',
    'session_key' => $sessionKey
  ]);

  if ($response['status'] === 'success') {
    $userData = $response['user'];
  } else {
    // invalid or expired session
    unset($_SESSION['session_key']);
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>AARC Portal</title>
  <link rel="stylesheet" href="baseStyle.css"> 
</head>

<body>
  <header>
    <?php if ($userData): ?>
      <?php include("nav.inc.php"); ?>
    <?php endif; ?>
  </header>

  <main>
    <?php
    // PAGE CONTENT HANDLER
    if (isset($_REQUEST['content'])) {

      $content = $_REQUEST['content'];
      include("$content.php");
    } 
    
    else {
      include("main.inc.php");
    }
    ?>
  </main>

  <footer>
    <?php include("footer.inc.php"); ?>
  </footer>

</body>

</html>