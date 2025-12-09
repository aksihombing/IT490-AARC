<?php require_once('includes/book.inc.php'); ?>


<!doctype html>
<html>

<head>
  <title>Book Details</title>
  <link rel="stylesheet" href="bootstrap-5.3.8/dist/css/bootstrap.css">
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
    integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
    crossorigin="anonymous"></script>
  <script src="bootstrap-5.3.8/dist/js/bootstrap.js"></script>
  <meta name="viewport" content="width=device-width, initial-scale=1">


  
</head>


<body>
<div class="container m-5"><!-- reusing the same container to match -->

  <!-- failed to collect book data -->
  <?php if (!$book || empty($book)): ?>
    <div class="alert alert-danger" role="alert"> <!-- added an alert -->
    <h2 class="h4 mb-2">Error loading book</h2>
    <p class="mb-0">No details available.</p>
</div>
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
    <h2 id="book-title" class="mb-2"><?php echo htmlspecialchars($book['title']); ?></h2>
    <p id="book-author" class="mb-4"><?php echo htmlspecialchars($book['author']); ?></p>

    <div class="card mb-4"> <!-- bootstrap card for book details -->
      <div class="row g-3 p-3"> <!-- bootstrap gird in card -->
        <div class="col-md-4 d-flex justify-content-center align-items-start"> 
      <img id="cover" class="cover img-fluid" alt="Book Cover" src="<?php echo htmlspecialchars($book['cover_url']); ?>">
        </div>

        <div class="col-md-8">

      <div id="book-data">
        <p class="mb-2"><strong>Average Rating</strong> <?php echo htmlspecialchars(round($book['ratings_average'],2)); ?> </p>
        
        <p class="mb-2"><strong>ISBN: </strong> <?php echo htmlspecialchars($book['isbn']); ?> </p>
        <p class="mb-2"><strong>Description: </strong> <?php echo htmlspecialchars($book['book_desc']); ?> </p>
        <p class="mb-2"><strong>First Published: </strong> <?php echo htmlspecialchars($book['publish_year']); ?> </p>

        <?php // FOR SUBJECTS, comma separated
          $subjects = json_decode($book['subjects'] ?? '[]', true); ?>
        <p class="mb-1"><strong>Subjects: </strong>

         <?php echo  htmlspecialchars(implode(', ', $subjects));
          ?>


        <!--
        <p><strong></strong>     </p>
        <p><strong></strong>     </p>
        <p><strong></strong>    </p>
  -->
  </p>
      </div>
    </div>
      </div>
    </div>
  </div>

    <form method="POST" class="mt-4">
      <input type="hidden" name="action" value="add_to_library">
      <button class="btn btn-dark" type="submit">Add to My Library</button>
    </form>

    <!-- CHIZZY -->
    <section class="mt-8">
      <div class="card">
      <div class="card-body">
      <h3 class="h5 mb-3">Write a Review</h3>
      <form id="reviewForm" method="POST">
        <input type="hidden" name="action" value="create_review">


        <div class="col-md-4">

        <label for="rating" class="form-label">Rating:</label>
          <select id="rating" name="rating" class="form-select" required>
            <option value="">Select...</option>
            <option>1</option>
            <option>2</option>
            <option>3</option>
            <option>4</option>
            <option>5</option>
          </select>
        </div>
        <div class="col-md-8"></div>
        </label>
        <br>
        <label for="body" class="form-label">Review:</label>
        <textarea id="body" rows="3" name="body" class="form-control" placeholder="Write your thoughts here..."></textarea>
        </div>
        <div class="col-12"></div>
        <button class="btn btn-dark" type="submit">Submit</button>
  </div>
      </form>
      </div>
      </div>
    </section>

    <section class="mt-5">
      <h3 class="h5 mb-3">User Reviews</h3>

      <div id="reviews"> <!-- reviews div -->
        <?php if (empty($reviews)): ?>
          <p class="text-muted">No reviews yet!</p>
        <?php else: ?>
          <div class="list-group"> <!-- added list group comp for reviews -->
          <?php foreach ($reviews as $review): ?>
            <div class="list-group-item"> <!-- card div -->
              <p class="mb-1">
                <strong> <?php echo htmlspecialchars($review['username'] ?? 'Anonymous'); ?> </strong>
                — <?php echo (int) ($review['rating'] ?? 0) ?>/5
              </p>
              <p class="mb-1"> <?php echo htmlspecialchars($review['body'] ?? ''); ?></p>
              <small class="text-muted"> <?php echo htmlspecialchars($review['created_at'] ?? ''); ?> </small>
            </div>
          <?php endforeach; ?>
          </div> <!-- ending list  -->
        <?php endif; ?>
      </div> <!-- close card -->
    </section>
    <!-- CHIZZY, END -->
  <?php endif; ?>
  </div> <!-- close container -->

</body>

</html>