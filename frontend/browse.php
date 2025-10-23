<?php

//  FOR RECENT BOOKS !! -------------
// to load pre-loaded book data from cache db
require_once(__DIR__ . '/../rabbitMQ/rabbitMQLib.inc');


// FOR GENERAL BROWSING
// https://www.php.net/manual/en/function.intval.php
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; // default page is 1 -- checks for get http request param
// index.php?content=browse&page={$_GET[page]}
$limit = 10;
$query = 'adventure'; // need a basic query for less api errors, PLEASE WORK
$browseBooks = [];

try {
    $client = new rabbitMQClient(__DIR__ . '/../rabbitMQ/host.ini', 'LibraryCollect'); // LibraryCollect to prevent clogging ??
    // browse books request
    $browseResponse = $client->send_request([
        'type' => 'book_search',
        'query' => $query,
        'limit' => $limit,
        'page' => $page
    ]);

    if ($browseResponse['status'] === 'success') {
        $browseBooks = $browseResponse['data'];
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>Error loading featured books: " . htmlspecialchars($e->getMessage()) . "</p>";
}


?>

<!-- BROWSING ----------------------- -->


<section id="browse-section">
    <h3>Browse Books</h3>
    <!-- Browse results; SHOULD use the same structure as search.php and recent_books but isnt working ?? -->
    <?php if (!empty($browseBooks)): ?>
        <ul>
            <?php foreach ($browseBooks as $book): ?>
                <li>
                    <?php $olid = urlencode($book['olid']); ?> <!-- no fallback method bc every entry SHOULD have olid -->
                    <a href="index.php?content=book&olid=<?php echo htmlspecialchars($olid); ?>">

                        <br><br>
                        <?php if (!empty($book['cover_url'])): ?>
                            <img src="<?php echo htmlspecialchars($book['cover_url']); ?>" alt="Cover" width="80">
                        <?php endif; ?>


                        <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                        by <?php echo htmlspecialchars($book['author']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Pagination links -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="index.php?content=browse&page=<?php echo $page - 1; ?>">Previous</a>
            <?php endif; ?>

            <a href="index.php?content=browse&page=<?php echo $page + 1; ?>">Next</a>
        </div>
    <?php else: ?>
        <p>No books found for this category.</p>
    <?php endif; ?> <!-- end if(!empty$browseBooks) -->
</section>
