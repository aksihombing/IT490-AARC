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
    <h1>Search The Library</h1>



    <form method="GET" action="index.php">
        <input type="hidden" name="content" value="search">

        <label for="query">Search Term:</label>
        <input type="text" name="query" id="query" placeholder="Enter book title or author"
            value="<?php echo htmlspecialchars($_GET['query'] ?? ''); ?>">

        <!-- SCRAPPED - search by title (search.json/q=query) or author (search.json/author=query)
        <label for="type">Search By:</label>
        <select name="type" id="type">
            <option value="title" < ?php echo ($_GET['type'] ?? '') === 'title'; ?>>Title</option>
            <option value="author" < ?php echo ($_GET['type'] ?? '') === 'author'; ?>>Author</option> 
        </select> 
        -->

        <button type="submit">Search</button>
    </form>

    <?php if ($error): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if (!empty($bookSearchResults)): ?>
        <h2>Results:</h2>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
            <!-- 1 card on mobile, 2 cards on small screens, 3 cards on medium, 4 cards on large (per row); uses grid breakpoint for grid-3 -https://getbootstrap.com/docs/5.3/layout/grid/
                -->
            <?php foreach ($bookSearchResults as $book):
                // WORK IN PROGRESS BC IDK WHAT IM DOING !!!
                $olid = urlencode($book['olid']);
                // used for book.php GET queries
                // echo "<p>OLID : $olid</p>";// DEBUGGING
                ?>

                <div class="col"> <!-- columnss -->
                    <div class="card h-100"> <!-- design per card, height=100 -->

                        <!-- book image -->
                        <?php if (!empty($book['cover_url'])): ?>
                            <img src="
                        <?php echo htmlspecialchars($book['cover_url']); ?>" alt="Book Cover" class="card-img-top">
                        <?php endif; ?> <!-- SHOULD ADD "ELSE" for when book cover is null-->

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