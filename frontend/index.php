<?php
session_start();
require_once(__DIR__ . '/../rabbitMQ/rabbitMQLib.inc');
require_once(__DIR__ . '/../rabbitMQ/RabbitMQServer.php');


$client = new rabbitMQClient("/../host.ini", "AuthValidate");

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

</head>

<body>
  <header>
    <nav>
      <ul>
        <?php if ($userData): ?>
          <!--<li><a href="index.php?content=dashboard">Dashboard</a></li>-->
          <li>You are logged in!</li>
          <li><a href="search.php">Search Books</a></li>
          <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
          <li><a href="index.php?content=login">Login</a></li>
          <li><a href="index.php?content=register">Register</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <main>
    <?php
    // PAGE CONTENT HANDLER
    if (isset($_REQUEST['content'])) {
      $allowedPages = ['dashboard', 'main']; // prevent arbitrary includes
      $content = $_REQUEST['content'];

      if (in_array($content, $allowedPages)) {
        include("$content.inc.php");
      } else {
        echo "<p>Page not found.</p>";
      }
    } else {
      include("main.inc.php");
    }
    ?>
  </main>

  <footer>
    <?php include("footer.inc.php"); ?>
  </footer>

</body>

</html>