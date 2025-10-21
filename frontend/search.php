<?php
require_once(__DIR__ . '/../rabbitMQ/rabbitMQLib.inc');
include __DIR__ . '/../links/book_link.inc.php';
session_start();
 

$results = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $searchType = $_GET['type'] ?? 'title';
    $query = trim($_GET['query'] ?? '');

    if ($query === '') {
        $error = "Please enter a search term.";
    } else {
        try {
            $client = new rabbitMQClient(__DIR__ . "../rabbitMQ/host.ini", "LibrarySearch");

            $request = [
                'type' => 'book_search',
                'searchType' => $searchType,
                'query' => $query
            ];

            $response = $client->send_request($request);

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

    <form method="GET">
        <!--  name "query" is used for searching by keyword   -->
        <label for="query">Search Term:</label>
        <input type="text" name="query" id="query" placeholder="Enter book title or author">

        <!-- name "type" is used to search by author and/or title specifically     -->
        <label for="type">Search By:</label>
        <select name="type" id="type">
            <option value="title">Title</option>
            <option value="author">Author</option>
        </select>

        <button type="submit">Search</button>
    </form>

    <?php if ($error): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if (!empty($results)): // results success?> 
        <h2>Results:</h2>
        <ul>
            <?php foreach ($results as $book): ?>
                <li>
                    <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                    by <?php echo htmlspecialchars($book['author']); ?>
                    (<?php echo htmlspecialchars($book['year']); ?>)
                </li>
            <?php endforeach; // an upgrade from it202, i love it! ?>
        </ul>
    <?php endif; ?>
</body>

</html>