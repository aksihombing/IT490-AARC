<?php
// simple test to call OpenLibrary Search API and print results

// example search term — you can change this or pass via GET
$query = $_GET['q'] ?? 'harry potter';

// prepare API URL
$encoded = urlencode($query);
$url = "https://openlibrary.org/search.json?q={$encoded}&limit=5";

// make GET request
$response = file_get_contents($url);

if ($response === false) {
    die(" Failed to fetch data from OpenLibrary API.\n");
}

// decode JSON response
$data = json_decode($response, true);

if (!isset($data['docs']) || empty($data['docs'])) {
    die(" No results found for '{$query}'.\n");
}

// print some information
echo "Found " . count($data['docs']) . " results for '{$query}':\n\n";

foreach ($data['docs'] as $book) {
    $title = $book['title'] ?? 'Unknown title';
    $author = isset($book['author_name'][0]) ? $book['author_name'][0] : 'Unknown author';
    $year = $book['first_publish_year'] ?? 'N/A';

    echo "- {$title} by {$author} ({$year})\n";
}
?>
