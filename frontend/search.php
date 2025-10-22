<?php
require_once(__DIR__ . '/../rabbitMQ/rabbitMQLib.inc');
include __DIR__ . '/../links/book_link.inc.php';
//session_start();

$results = []; // update results while doBookSearch loop
$error = ''; // error catching

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $searchType = $_GET['type'] ?? 'title';
    $query = trim($_GET['query'] ?? '');

    if ($query === '') {
        $error = "Please enter a search term.";
    } else {
        try {
            $client = new rabbitMQClient(__DIR__ . "/../rabbitMQ/host.ini", "LibrarySearch");

            $request = [
                'type' => 'book_search',
                'searchType' => $searchType,
                'query' => $query
            ];

            $response = $client->send_request($request);
            //var_dump($response); //debugging 

            if ($response['status'] === 'success') {
                $results = $response['data'];
            } else {
                $error = $response['message'] ?? 'Unknown error from server.';
            }
        } catch (Exception $e) {
            $error = "Error connecting to search service: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>

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

    <?php if (!empty($results)): ?>
        <h2>Results:</h2>
        <ul>
            <?php foreach ($results as $book):
                // WORK IN PROGRESS BC IDK WHAT IM DOING !!!
                $olid = urlencode($book['olid']);
                // used for book.php GET queries
                ?>
                <br><br> <!-- might be best to do a css thing here but might have to wait off a bit -->
                <li>
                    <?php if (!empty($book['cover_url'])): ?>
                        <br>
                        <img src="
                        <?php echo htmlspecialchars($book['cover_url']); ?>" alt="Cover" width="80">
                    <?php endif; ?>
                    <a href="index.php?content=book&olid=<?php htmlspecialchars($olid); ?>
                    ">
                        <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                        by <?php echo htmlspecialchars($book['author']); ?>
                        (<?php echo htmlspecialchars($book['publish_year']); ?>)

                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>

</html>