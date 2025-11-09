<?php
// Start session and check if user is logged in.
// If not, send them back to login page.
// FROM CHIZZY
// edited by Rea

// DEBUGGING
require_once('includes/my_library.inc.php');
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

      <?php foreach ($libraryBooks as $book): ?> <!-- DISPLAY EACH BOOK -->
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
        </div> <!-- end card -->

      <?php endforeach; ?> <!-- end display each book -->


        <br>
        <br>
        <br>

      <!-- FOR EACH RECOMMENDED BOOK, same as displaying library books -->
      <h3> Recommended Books </h3>
      <?php if (!empty($recommendedBooks)): ?>
        <?php foreach ($recommendedBooks as $r_book): ?> <!-- DISPLAY EACH BOOK -->
          <div class="card">
            <img class="cover" src="<?php echo htmlspecialchars($r_book['cover_url']) ?>" alt="Book Cover">

            <h3><a href="index.php?content=book&olid=<?php echo htmlspecialchars($r_book['olid']); ?>">
                <?php echo htmlspecialchars($r_book['title']) ?>
              </a></h3>

            <p><?php echo htmlspecialchars($r_book['author']) ?></p>

           <!-- <p><strong>ISBN:</strong> < ? php echo htmlspecialchars($r_book['isbn']) ? ></p> -->
            <!-- removed ISBN from showing -->

            <p><strong>Published:</strong> <?php echo htmlspecialchars($r_book['publish_year']) ?></p>
          </div> <!-- end card -->

        <?php endforeach; ?> <!-- end display each book -->
        <?php else: ?>
          <p>No recommendations found.</p>
      <?php endif; ?> <!-- end else -->

    </div> <!-- end grid -->
  <?php endif; ?> <!-- end else -->
</body>

</html>