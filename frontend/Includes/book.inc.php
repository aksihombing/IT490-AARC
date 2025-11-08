<?php
require_once(__DIR__ . '/../rabbitMQ/rabbitMQLib.inc');
//session_start();
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


// --------- ADD TO LIBRARY
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_to_library'){
      $addLibraryClient = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'LibraryPersonal');
      $addLibraryClient->send_request([
        'type'    => 'library.personal.add',
        'user_id' => $_SESSION['uid'],
        'works_id' => $olid,
      ]);

      
    

      header("Location: index.php?content=book&olid=" . urlencode($olid));
      exit;// should we reedirect after to show it works?
      
      echo "<p>Book added to your library!</p>";

    }

    // ------------- CREATE REVIEW
    //handling  the review submission
    if ($action === 'create_review') {
      $rating  = $_POST['rating']  ?? 0;
      $comment = $_POST['comment'] ?? '';

      $createReviewClient = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'CreateReviews');
      $createReviewClient->send_request([
        'type'     => 'library.review.create',
        'user_id'  => $_SESSION['uid'],
        'works_id' => $olid,
        'rating'   => $rating,
        'comment'  => $comment,
      ]);

      header("Location: index.php?content=book&olid=" . urlencode($olid));
      exit;
    }
  } catch (Exception $e) {
    $error = "Error processing request: " . $e->getMessage();
  }
}




// -------------- DO BOOK DETAILS
try {
  $bookDetailsClient = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'LibraryDetails');
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

$reviews=[];
try {
  $listReviewsClient = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'ListReviews');
  $resp = $listReviewsClient->send_request([
    'type'     => 'library.reviews.list',
    'works_id' => $olid,
  ]);
  if ($resp['status'] === 'success') {
    $reviews = $resp['items'];
  }
  } catch (Exception $e) {

}

?>