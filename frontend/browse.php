<?php include __DIR__ . '/includes/browse.inc.php'; ?>

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
