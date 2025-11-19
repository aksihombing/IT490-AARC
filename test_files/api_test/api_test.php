<?php
// simple test to call OpenLibrary Search API and print results

// what data are we collecting for the cache ??


/*
Taken from OPEN LIBRARY's doc
q = keyword or title
title = title
author = author

The URL format for API is simple. Take the search URL and add .json to the end. Eg:

https://openlibrary.org/search.json?q=the+lord+of+the+rings
https://openlibrary.org/search.json?title=the+lord+of+the+rings
https://openlibrary.org/search.json?author=tolkien&sort=new
https://openlibrary.org/search.json?q=the+lord+of+the+rings&page=2
https://openlibrary.org/search/authors.json?q=twain
*/


// example search term
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
