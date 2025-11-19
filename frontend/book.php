<?php require_once('includes/book.inc.php'); ?>


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
        <p><strong>Average Rating</strong> <?php echo htmlspecialchars(round($book['ratings_average'],2)); ?> </p>
        <br>
        <p><strong>ISBN: </strong> <?php echo htmlspecialchars($book['isbn']); ?> </p>
        <p><strong>Description: </strong> <?php echo htmlspecialchars($book['book_desc']); ?> </p>
        <p><strong>First Published: </strong> <?php echo htmlspecialchars($book['publish_year']); ?> </p>

        <?php // FOR SUBJECTS, comma separated
          $subjects = json_decode($book['subjects'] ?? '[]', true);

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
      <div id="reviews"> <!-- reviews div -->
        <?php if (empty($reviews)): ?>
          <p>No reviews yet!</p>
        <?php else: ?>
          <?php foreach ($reviews as $review): ?>
            <div class="card"> <!-- card div -->
              <p>
                <strong> <?php echo htmlspecialchars($review['username'] ?? 'Anonymous'); ?> </strong>
                — <?php echo (int) ($review['rating'] ?? 0) ?>/5
              </p>
              <p> <?php echo htmlspecialchars($review['body'] ?? ''); ?></p>
              <small> <?php echo htmlspecialchars($review['created_at'] ?? ''); ?> </small>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div> <!-- card div -->
    </section>
    <!-- CHIZZY, END -->
  <?php endif; ?>

</body>

</html>