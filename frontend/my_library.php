<?php
// Start session and check if user is logged in.
// If not, send them back to login page.
// FROM CHIZZY
// edited by Rea
session_start();
require_once __DIR__ . '/../rabbitMQ/rabbitMQLib.inc';


$userId = $_SESSION['user_id'];// getting the user id from the session
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
  $listclient = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'LibraryPersonal');
  $resp = $listclient->send_request([
    'type'    => 'library.personal.list',
    'user_id' => $userId,
    
  ]);
    echo "<p>" . print_r($resp, true) . "</p>"; // DEBUGGING - checking response
if ($resp['status'] === 'success') {
    $library = $resp['items'];
  } else {
    $error = $resp['message'] ?? 'Unknown error from server.';
  }
} catch (Exception $e) {
  $error = "Error connecting to library service: " . $e->getMessage();
}

try {
  $detailsclient = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'LibraryDetails');
  foreach ($library as &$book) {
    $olid = $book['works_id'] ?? '';
    if (!$olid) continue; // ask if this should 

    try {
      $details = $detailsclient->send_request([
        'type' => 'book_details',
        'olid' => $olid 
      ]);

       echo "<p>" . print_r($resp, true) . "</p>"; // DEBUGGING - checking response

      if ($details['status'] === 'success') {
        $bookDetails = $details['items'] ?? $details['data'] ?? [];

       
        $book['title']        = $bookDetails['title'] ?? 'Unknown Title';
        $book['author']       = $bookDetails['author'] ?? 'Unknown Author';
        $book['cover_url']    = $bookDetails['cover_url'] ?? '';
        $book['publish_year'] = $bookDetails['publish_year'] ?? 'Unknown Year';
        $book['isbn']         = $bookDetails['isbn'] ?? 'N/A';
      }
    } catch (Exception $e) {
      error_log("Error getting details: " . $e->getMessage());
    }
  }
 
} catch (Exception $e) {
  $error = "Error connecting to LibraryDetails: " . $e->getMessage();
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
        <div class="card">
          <img class="cover" src="<?php echo htmlspecialchars($book['cover_url']) ?>" alt="Book Cover">
          <h3><a href="index.php?content=book&olid=<?php echo htmlspecialchars($olid); ?>">
            <?php echo htmlspecialchars($book['title']) ?>
          </a></h3>
          <p><?php echo htmlspecialchars($book['author']) ?></p>
          <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']) ?></p>
          <p><strong>Published:</strong> <?php echo htmlspecialchars($book['publish_year']) ?></p>

          <form method="POST">
            <input type="hidden" name="olid" value="<?php echo htmlspecialchars($book['works_id']) ?>">
            <button type="submit" class="btn">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</body>
</html>
