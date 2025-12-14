<?php

//  FOR RECENT BOOKS !! -------------
// to load pre-loaded book data from cache db
require_once(__DIR__ . '/../../rabbitMQ/rabbitMQLib.inc');
require_once(__DIR__ . '/../../rabbitMQ/log_producer.php');


// FOR GENERAL BROWSING
// https://www.php.net/manual/en/function.intval.php
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; // default page is 1 -- checks for get http request param
// index.php?content=browse&page={$_GET[page]}
$limit = 5;
$query = 'adventure'; // need a basic query for less api errors, PLEASE WORK
$browseBooks = [];

try {
    $client = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'LibrarySearch'); // LibraryCollect to prevent clogging ?? maybe ??
    // browse books request
    $browseResponse = $client->send_request([
        'type' => 'book_search',
        'query' => $query,
        'limit' => $limit,
        'page' => $page
    ]);

    if ($browseResponse['status'] === 'success') {
        $browseBooks = $browseResponse['data'];
        log_event("frontend", "info", "Browse books loaded successfully " . $page);
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>Error loading featured books: " . htmlspecialchars($e->getMessage()) . "</p>";
    log_event("frontend", "error", "Error connecting: " . ($e->getMessage()));
}


?>