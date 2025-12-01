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
  <link rel="stylesheet" href="bootstrap-5.3.8/dist/css/bootstrap.css">
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
    integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
    crossorigin="anonymous"></script>
  <script src="bootstrap-5.3.8/dist/js/bootstrap.js"></script>
  <meta name="viewport" content="width=device-width, initial-scale=1">


  
  <link rel="stylesheet" href="baseStyle.css">
</head>

<body>

 <div class="container m-5"><!-- reusing the same container to match -->
  <!-- Page heading -->
  <h2 class ="mb-4">My Library</h2>   <!-- added bottom margin -->

 
  <?php if (empty($libraryOlidList)): ?>
    <p>You haven’t added any books yet.</p>
    <p><a href="index.php?content=search" class="btn btn-dark">Go to Search Page →</a></p>
  <?php else: ?>
  <!-- changed button class -->
    
 <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
           <!-- responsive grid from search to match -->
      <?php foreach ($libraryBooks as $book): ?> <!-- DISPLAY EACH BOOK -->
        <div class="col"> <!-- columnss -->
        <div class="card h-100"> <!-- keeping the height at 100% -->
          <?php if (!empty($book['cover_url'])):?>
          <img  src="<?php echo htmlspecialchars($book['cover_url']) ?>" alt="Book Cover" class="card-img-top"> <!-- added the bootstrap card img class -->
          <?php else: ?>
            <img src="images/gray.jpg" class="card-img-top" alt="No book cover found"> <!-- added bookcover alternative, if no cover found -->
       <?php endif; ?>
<div class="card-body">
  <h4 class="card-title">
          <a href="index.php?content=book&olid=<?php echo htmlspecialchars($book['olid']); ?>" class="stretched-link text-decoration-none">
              <?php echo htmlspecialchars($book['title']); ?>
            </a></h4>
<!-- added the stretched link -->
 <p class="card-text mb-1"> <!-- more padding/margin -->
          <?php echo htmlspecialchars($book['author']); ?></p>

<p class="card-text text-muted"> <!-- muted the text for the publish year -->
          <strong>Published:</strong> <?php echo htmlspecialchars($book['publish_year']) ?></p>

          <form method="POST" class="mt-3"> <!-- added a margin on top-->
            <input type="hidden" name="works_id" value="<?php echo htmlspecialchars($book['olid']) ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
          </form> <!-- changed button style to danger type -->
        </div> 
        </div> 
        </div> <!-- end card -->

      <?php endforeach; ?> <!-- end display each book -->

</div> 
        <br>
        <br>
        <br>

      <!-- FOR EACH RECOMMENDED BOOK, same as displaying library books -->
      <h3 class="mb-3"> Recommended Books </h3> <!-- added bottom margin -->
      <?php if (!empty($recommendedBooks)): ?>
         <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
           <!--  grid from search to match again-->
        <?php foreach ($recommendedBooks as $r_book): ?> <!-- DISPLAY EACH BOOK -->
          <div class="col">
          <div class="card h-100"> <!-- keeping height at 100% -->
            <?php if (!empty($r_book['cover_url'])):?>
            <img  src="<?php echo htmlspecialchars($r_book['cover_url']); ?>" alt="Book Cover" class="card-img-top"> <!-- added the bootstrap card img class -->
         <?php else: ?>
              <img src="images/gray.jpg" class="card-img-top" alt="No book cover found"> <!-- added bookcover alternative, if no cover found -->
        <?php endif; ?>
    <div class="card-body">
    <h4 class="card-title">
            <a href="index.php?content=book&olid=<?php echo htmlspecialchars($r_book['olid']); ?>"
              class="stretched-link text-decoration-none">
                <?php echo htmlspecialchars($r_book['title']) ?>
              </a></h4>

  <p class="card-text mb-1"> <!-- more padding/margin -->
            <?php echo htmlspecialchars($r_book['author']) ?></p>

<p class="card-text text-muted"> <!-- muted the text for the publish year -->
            <strong>Published:</strong> <?php echo htmlspecialchars($r_book['publish_year']) ?>
          </p>
          </div> <!-- end card body  -->
</div> <!-- end card  -->
          </div> <!-- end col -->
          </div>

        <?php endforeach; ?> <!-- end display each book -->
        </div> <!-- end -->
        <?php else: ?>
          <p>No recommendations found.</p>
      <?php endif; ?> <!-- end else -->

    
  <?php endif; ?> <!-- end else -->
  
    </div>
</body>

</html>
