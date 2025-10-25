<?php
// Start session and check if user is logged in.
// If not, send them back to login page.
// FROM CHIZZY
// edited by Rea
session_start();
require_once __DIR__ . '/../rabbitMQ/rabbitMQLib.inc';


$userId = $_SESSION['user_id'];// getting the user id from the session
$error = '';
$libraryOlidList = []; // for all olids in the library

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


function getPersonalLibBookDetails($plib_olid)
{
  try {
    $bookDetailsClient = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'LibraryDetails');

    $response = $bookDetailsClient->send_request([
      'type' => 'book_details',
      'olid' => $plib_olid
    ]);

    if (($response['status'] === 'success') && is_array($response)) {

      $plib_book = $response['data'];
      return $plib_book; // return the array of details
      /*
       $bookDetailsResults= [ // this gets returns to the webserver
      'olid' => $olid,
      'title' => $title,
      'subtitle' => $subtitle,
      'author' => $author,
      'isbn' => $isbn,
      'book_desc' => $book_desc,
      'publish_year' => $publish_year,
      'ratings_average' => $ratings_average,
      'ratings_count' => $ratings_count,
      'subjects' => $subjects,
      'person_key' => $person_key,
      'place_key' => $place_key,
      'time_key' => $time_key,
      'cover_url' => $cover_url
    ];
      */
    }
    else{
      return null;
    }
  } 
  catch (Exception $e) {
    return null; // idk what to return here
  }
}



// RUNS WHEN THE PAGE LOADS !! ------------

// libraryOlidList -> all olids from the personal library
// libraryBooks -> book details for all olids

try {
  $bookListClient = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'LibraryPersonal');
  $resp = $bookListClient->send_request([
    'type' => 'library.personal.list',
    'user_id' => $userId,

  ]);
  echo "<p>" . print_r($resp, true) . "</p>"; // DEBUGGING - checking response
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

if (!empty($libraryOlidList)){
  foreach ($libraryOlidList as $singleBook){
    $olid = $singleBook['works_id'] ?? $singleBook; // works_id call is from Personal Library List call

    $details = getPersonalLibBookDetails($olid);
    if ($details){
      $libraryBooks[] = $details; // adds book details in an array per olid
      echo "<p>getPersonalLib foreach:" . print_r($libraryBooks, true) . "</p>"; // DEBUGGING - checking response
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
          <h3><a href="index.php?content=book&olid=<?php echo htmlspecialchars($olid); ?>">
              <?php echo htmlspecialchars($book['title']) ?>
            </a></h3>
          <p><?php echo htmlspecialchars($book['author']) ?></p>
          <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']) ?></p>
          <p><strong>Published:</strong> <?php echo htmlspecialchars($book['publish_year']) ?></p>

          <form method="POST">
            <input type="hidden" name="olid" value="<?php echo htmlspecialchars($olid) ?>">
            <button type="submit" class="btn">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>


    </div>
  <?php endif; ?>
</body>

</html>