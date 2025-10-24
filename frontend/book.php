<?php
require_once(__DIR__ . '/../rabbitMQ/rabbitMQLib.inc');

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
  // POST functions are used for add_to_library and create_review
  try {
    $action = $_POST['action'] ?? '';


    // ADD TO LIBRARY (POST)
    if ($action === 'add_to_library') {
      $addLibraryClient = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'LibraryPersonal');
      $addLibraryClient->send_request([
        'type' => 'library.personal.add',
        'user_id' => $_SESSION['uid'],
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

      $createReviewClient = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'CreateReviews');
      $createReviewClient->send_request([
        'type' => 'library.review.create',
        'user_id' => $_SESSION['uid'],
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

$reviews = [];
try {
  $listReviewsClient = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'ListReviews');
  $resp = $listReviewsClient->send_request([
    'type' => 'library.reviews.list',
    'works_id' => $olid,
  ]);
  if ($resp['status'] === 'success') {
    $reviews = $resp['items'];
  }
} catch (Exception $e) {

}

?>


<!doctype html>
<html>

<head>
  <title>Book Details</title>
  <link rel="stylesheet" href="baseStyle.css">
</head>


<body>
  <!-- failed to collect book data -->
  <?php if (!$book || empty($book)): ?>
    <h2>Error loading book</h2>
    <p>No details available.</p>

    <!-- book success -->

    <!-- FOR REFERENCE, this is what information is returned from doBookDetails

   $searchbookresults[] = [ // this gets returns to the webserver
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

  -->


  <?php else: ?>
    <!--<p>< php var_dump($book)? ></p>  DEBUGGING -->
    <h2 id="book-title"><?php echo htmlspecialchars($book['title']); ?></h2>
    <p id="book-author"><?php echo htmlspecialchars($book['author']); ?></p>

    <div class="book-info">
      <img id="cover" class="cover" alt="Book Cover" src="<?php echo htmlspecialchars($book['cover_url']); ?>">

      <div id="book-data">
        <p><strong>Rating</strong> <?php echo htmlspecialchars($book['ratings_average']); ?> </p>
        <br>
        <p><strong>ISBN: </strong> <?php echo htmlspecialchars($book['isbn']); ?> </p>
        <p><strong>Description: </strong> <?php echo htmlspecialchars($book['book_desc']); ?> </p>
        <p><strong>First Published: </strong> <?php echo htmlspecialchars($book['publish_year']); ?> </p>

        <?php // FOR SUBJECTS, comma separated
          $subjects = json_decode($books['subjects'] ?? '[]', true);

          echo "<p><strong>Subjects: </strong>" . htmlspecialchars(implode(', ', $subjects)) . "</p>";
          ?>

        <!--
        <p><strong></strong>     </p>
        <p><strong></strong>     </p>
        <p><strong></strong>    </p>
  -->

      </div>
    </div>

    <form method="POST" style="margin-top:12px;">
      <input type="hidden" name="action" value="add_to_library">
      <button class="btn" type="submit">Add to My Library</button>
    </form>

    <!-- CHIZZY -->
    <section>
      <h3>Write a Review</h3>
      <form id="reviewForm" method="POST">
        <input type="hidden" name="action" value="create_review">
        <label>Rating:
          <select id="rating" name="rating" required>
            <option value="">Select...</option>
            <option>1</option>
            <option>2</option>
            <option>3</option>
            <option>4</option>
            <option>5</option>
          </select>
        </label>
        <br>
        <label>Review:</label><br>
        <textarea id="body" rows="3" name="body" placeholder="Write your thoughts here..."></textarea>
        <br>
        <button class="btn" type="submit">Submit</button>
      </form>
    </section>

    <section>
      <h3>User Reviews</h3>
      <div id="reviews"></div>
      <?php if (empty($reviews)): ?>
        <p>No reviews yet!</p>
      <?php else: ?>
        <?php foreach ($reviews as $review): ?>
          <div class="card">
            <p>
              <strong>  <?php htmlspecialchars($review['username'] ?? 'User'); ?> </strong>
              — <?php (int) ($review['rating'] ?? 0) ?>/5</p>
            <p> <?php htmlspecialchars($review['body'] ?? ''); ?></p>
            <small> <?php htmlspecialchars($review['created_at'] ?? ''); ?> </small>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
      </div>
    </section>
    <!-- CHIZZY, END -->
  <?php endif; ?>

</body>

</html>