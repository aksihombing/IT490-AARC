<?php
// Start session and check if user is logged in.
// If not, send them back to login page.
// FROM CHIZZY
// edited by Rea

// DEBUGGING
error_reporting(E_ALL);
ini_set('display_errors', 1);


//session_start();
require_once __DIR__ . '/../rabbitMQ/rabbitMQLib.inc';


$userId = $_SESSION['user_id'];// getting the user id from the session
$error = ''; // idk if we need this

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $worksID = $_POST['works_id'] ?? '';
    $client = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'LibraryPersonal');
    $client->send_request([
      'type' => 'library.personal.remove',
      'user_id' => $userId,
      'works_id' => $worksID

    ]);

    header('Location: index.php?content=my_library');
    exit;
  } catch (Exception $e) {
    $error = "Error connecting to library: " . $e->getMessage();

  }

}


function getPLibDetails($plib_olid)
{
  try {
    $bookDetailsClient = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'LibraryDetails');

    $response = $bookDetailsClient->send_request([
      'type' => 'book_details',
      'olid' => $plib_olid
    ]);

    if (($response['status'] === 'success') && isset($response['data']) && is_array($response['data'])) { // checks success, if data is set, and if data is array
      $plib_bookdata = $response['data'];
      return [
        'olid' => $plib_olid,
        'title' => $plib_bookdata['title'] ?? 'Unknown Title',
        'author' => $plib_bookdata['author'] ?? 'Unknown Author',
        'isbn' => $plib_bookdata['isbn'] ?? 'N/A',
        'cover_url' => $plib_bookdata['cover_url'] ?? 'default-cover.png', // fallback if missing
        'publish_year' => $plib_bookdata['publish_year'] ?? 'Unknown'
      ];

    } else {
      return null;
    }
  } catch (Exception $e) {
    return null; // idk what to return here
  }
}



// RUNS WHEN THE PAGE LOADS !! ------------

// libraryOlidList -> all olids from the personal library
// libraryBooks -> book details for all olids

$libraryOlidList = []; // for all olids in the library

try {
  $bookListClient = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'LibraryPersonal');
  $resp = $bookListClient->send_request([
    'type' => 'library.personal.list',
    'user_id' => $userId,

  ]);
  //echo "<p>" . print_r($resp, true) . "</p>"; // DEBUGGING - checking response
  if ($resp['status'] === 'success') {
    $libraryOlidList = $resp['items'];
  } else {
    $error = $resp['message'] ?? 'Unknown error from server.';
  }
} catch (Exception $e) {
  $error = "Error connecting to library service: " . $e->getMessage();
}

// after the library is loaded ..
$libraryBooks = [];

if (!empty($libraryOlidList)) {
  foreach ($libraryOlidList as $singleBook) {
    $olid = $singleBook['works_id'] ?? $singleBook; // works_id call is from Personal Library List call

    $details = getPLibDetails($olid);
    if ($details) {
      $libraryBooks[] = $details; // adds book details in an array per olid
      //echo "<p>getPLibDetails foreach:" . print_r($libraryBooks, true) . "</p>"; // DEBUGGING - checking response
    }
  }
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

  <?php if (empty($libraryOlidList)): ?>
    <p>You haven’t added any books yet.</p>
    <p><a href="index.php?content=search" class="btn">Go to Search Page →</a></p>
  <?php else: ?>
    <div class="grid">

      <?php foreach ($libraryBooks as $book): ?>
        <div class="card">
          <img class="cover" src="<?php echo htmlspecialchars($book['cover_url']) ?>" alt="Book Cover">

          <h3><a href="index.php?content=book&olid=<?php echo htmlspecialchars($book['olid']); ?>">
              <?php echo htmlspecialchars($book['title']) ?>
            </a></h3>

          <p><?php echo htmlspecialchars($book['author']) ?></p>

          <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']) ?></p>

          <p><strong>Published:</strong> <?php echo htmlspecialchars($book['publish_year']) ?></p>

          <form method="POST">
            <input type="hidden" name="works_id" value="<?php echo htmlspecialchars($book['olid']) ?>">
            <button type="submit" class="btn">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>


    </div>
  <?php endif; ?>
</body>

</html>