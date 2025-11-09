<?php
require_once(__DIR__ . '/../../rabbitMQ/rabbitMQLib.inc');

/*
PULLED CHIZZYS CODE
edited by Rea

// url format -> /index.php?content=book&olid={OLID}
*/

// idk if this is necessar
//if (!isset($_SESSION['session_key'])) { header("Location: index.php"); exit; }

/*
FOR FRONTEND FOR EASIER COPY AND PASTING LINKS

<a href="index.php?content=book&olid=<?php echo $olid; ?>">
$olid = urlencode($book['olid'])
*/


// validate OLID request
$olid = $_GET['olid'];
if ($olid == '') {
  http_response_code(400);
  echo "<p>ERROR: Missing OLID in request.</p>";
  exit;
}

// check uid and username
$userId = $_SESSION['user_id'];
//$username = $_SESSION['username'];

// --------- ADD TO LIBRARY
$error = ''; // error catching

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // POST functions are used for add_to_library and create_review
  try {
    $action = $_POST['action'] ?? '';


    // ADD TO LIBRARY (POST)
    if ($action === 'add_to_library') {
      $addLibraryClient = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'LibraryPersonal');
      $addLibraryClient->send_request([
        'type' => 'library.personal.add',
        'user_id' => $userId,
        'works_id' => $olid,
      ]);

      header("Location: index.php?content=book&olid=" . urlencode($olid));
      exit;// should we reedirect after to show it works?

      echo "<p>Book added to your library!</p>";

    }

    // ------------- CREATE REVIEW (POST)
    //handling  the review submission
    if ($action === 'create_review') {
      $rating = $_POST['rating'] ?? 0;
      $body = $_POST['body'] ?? '';

      $createReviewClient = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'CreateReviews');
      $createReviewClient->send_request([
        'type' => 'library.review.create',
        'user_id' => $userId,
        'works_id' => $olid,
        'rating' => $rating,
        'body' => $body,
      ]);

      header("Location: index.php?content=book&olid=" . urlencode($olid));
      exit;
    }
  } catch (Exception $e) {
    $error = "Error processing request: " . $e->getMessage();
  }
}


// both book_details and ListReviews are run when the page is loaded -- doesnt rely on any other http request methods

// -------------- DO BOOK DETAILS
try {
  $bookDetailsClient = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'LibraryDetails');
  $response = $bookDetailsClient->send_request([
    'type' => 'book_details',
    'olid' => $olid
  ]);
} catch (Exception $e) {
  $response = [
    'status' => 'error',
    'message' => 'Unable to connect to LibraryDetails' . $e->getMessage()
  ];
}

$book = [];
if (($response['status'] === 'success') && is_array($response)) {
  //$book = json_decode($response['data'], true); //i dont think we need to decode the json if its already returned as an array of data
  $book = $response['data'];
}


// ------------- LIST REVIEWS
//fetch reviews and then list reviews

$reviews = [];
try {
  $listReviewsClient = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'ListReviews');
  $resp = $listReviewsClient->send_request([
    'type' => 'library.review.list',
    'works_id' => $olid
  ]);
  //echo "<p>" . print_r($resp, true) . "</p>"; // DEBUGGING - checking response
  if ($resp['status'] === 'success' && is_array($resp['items'])) {
    $reviews = $resp['items'];
  }
  else {
    $error = "Failed to load reviews: " . ($resp['message'] ?? 'Unknown error');
  }
} catch (Exception $e) {
  $resp = [
    'status' => 'error',
    'message' => 'Unable to connect to ListReviews' . $e->getMessage()
  ];
}

?>