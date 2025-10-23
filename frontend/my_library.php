<?php
// Start session and check if user is logged in.
// If not, send them back to login page.
// FROM CHIZZY
// edited by Rea
session_start();
require_once __DIR__ . '/../rabbitMQ/rabbitMQLib.inc';


$userId = $_SESSION['uid'];// getting the user id from the session
$error = '';
$library = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
  try {
    $worksID =$_POST['works_id']?? '';
    $client = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'LibraryPersonal');
     $client->send_request([
      'type'    => 'library.personal.remove',
      'user_id' => $userId,
      'works_id' => $worksID
      
    ]);

    header('Location: index.php?content=my_library');
    exit;
  } catch (Exception $e) {
    $error = "Error connecting to library: " . $e->getMessage();

  }

}

try {
  $client = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'LibraryPersonal');
  $resp = $client->send_request([
    'type'    => 'library.personal.list',
    'user_id' => $userId,
    
  ]);
if ($resp['status'] === 'success') {
    $library = $resp['items'];
  } else {
    $error = $resp['message'] ?? 'Unknown error from server.';
  }
} catch (Exception $e) {
  $error = "Error connecting to library service: " . $e->getMessage();
}


?>







<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>My Library</title>
  <!-- linking your external CSS file for styling -->
  <link rel="stylesheet" href="baseStyle.css">
</head>
<body>
  <!-- Page heading -->
  <h2>My Library</h2>

 <?php if (empty($library)): ?>
  <p>You haven’t added any books yet.</p>
  <p><a href="index.php?content=search" class="btn">Go to Search Page →</a></p>
<?php else: ?>
<div class="grid">
    <?php foreach ($library as $book): ?>
      <?php
        $worksId = htmlspecialchars($book['works_id']);
        $title   = htmlspecialchars($book['title'] ?? 'Unknown Title');
        $author  = htmlspecialchars($book['author'] ?? 'Unknown Author');
        $cover   = htmlspecialchars($book['cover_url'] ?? '');
      ?>
      <div class="card">
        <?php if ($cover): ?>
          <img class="cover" src="<?php echo $cover; ?>" alt="Cover">
        <?php endif; ?>
        <h4><a href="book.php?works_id=<?php echo $worksId; ?>"><?php echo $title; ?></a></h4>
        <p><?php echo $author; ?></p>
        <form method="POST">
          <input type="hidden" name="works_id" value="<?php echo $worksId; ?>">
          <button type="submit" class="btn">Remove</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

</body>
</html>
