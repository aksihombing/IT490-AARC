<?php
require_once('includes/search.inc.php');
?>

<!DOCTYPE html>
<html>
<!-- 
    https://www.w3schools.com/bootstrap5/bootstrap_cards.php 
     https://getbootstrap.com/docs/5.3/components/card/
    -->

<head>
    <title>Book Search</title>
</head>

<body>
    <div class="container align-self-center d-flex justify-content-center mt-4">
        <!-- searchbar -->
        <div class="border border-secondary-subtle rounded p-4 w-100" style="max-width:600px;">
            <form name="search" action="index.php" method="GET">
                <input type="hidden" name="content" value="search">
                <div class="form-group col-md-12 mb-3">
                    <label for="query">Search the AARC Library:</label>
                    <input type="text" class="form-control" id="query" name="query"
                        placeholder="Enter title or author name"
                        value="<?php echo htmlspecialchars($_GET['query'] ?? ''); ?>">
                </div>


                <button type="submit" class="btn btn-dark">Submit</button>
            </form>
        </div>
        <?php
        if (isset($_GET['error'])) {
            echo "<p style='color:red;'>Login Failed: " . htmlspecialchars($_GET['error']) . "</p>";
        }
        ?>
        <?php if ($error): ?>
            <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
    </div>




    <?php if (!empty($bookSearchResults)): ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
            <!-- 1 card on mobile, 2 cards on small screens, 3 cards on medium, 4 cards on large (per row); uses grid breakpoint for grid-3 -https://getbootstrap.com/docs/5.3/layout/grid/
                https://getbootstrap.com/docs/5.0/utilities/position/
                
                -->
            <?php foreach ($bookSearchResults as $book):
                // WORK IN PROGRESS BC IDK WHAT IM DOING !!!
                $olid = urlencode($book['olid']);
                // used for book.php GET queries
                // echo "<p>OLID : $olid</p>";// DEBUGGING
                ?>

                <div class="col"> <!-- columnss -->
                    <div class="card h-100"> <!-- design per card, height=100% -->
                        <!-- book image -->
                        <div class="position-relative">
                            <?php if (!empty($book['cover_url']) && str_contains($book['cover_url'], 'https')): ?>
                                <img src="
                        <?php echo htmlspecialchars($book['cover_url']); ?>" alt="Book Cover" class="card-img-top">
                            <?php else: ?>
                                <img src="images/gray.jpg" class="card-img-top" alt="No book cover available">
                                <div class="position-absolute top-50 start-50 text-white px-2 py-1">No Book Cover Available
                                </div>
                            <?php endif; ?> <!-- SHOULD ADD " ELSE" for when book cover is null-->
                        </div>

                        <!-- book info -->
                        <div class="card-body">
                            <h5 class="card-title">
                                <!-- display title but also stretch each book link across the entire card so that it is all clickable. 
                             https://getbootstrap.com/docs/5.3/helpers/stretched-link/
                        -->
                                <a href="index.php?content=book&olid=<?php echo $olid; ?>"
                                    class="stretched-link text-decoration-none">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </a>
                            </h5>
                            <!-- other details -->
                            <p class="card-text mb-1">
                                <?php echo htmlspecialchars($book['author']); ?>
                            </p>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars($book['publish_year']); ?>
                            </p>
                        </div>

                    </div><!-- end card body -->

                </div><!-- end card col -->

            <?php endforeach; ?>

        </div> <!-- END OF OVERALL CARD DISPLAYS-->
    <?php endif; ?>
</body>

</html>