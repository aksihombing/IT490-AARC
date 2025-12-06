<?php
//  FOR RECENT BOOKS !! -------------
// to load pre-loaded book data from cache db
require_once(__DIR__ . '/../../rabbitMQ/rabbitMQLib.inc');

$recentBooks = []; // for recent books call


try {
  $client = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'LibrarySearch'); // no special queue for LibrarySearch

  // Recent books request
  $recentResponse = $client->send_request(['type' => 'recent_books']);

  if ($recentResponse['status'] === 'success') {
    $recentBooks = $recentResponse['data'];
  }

} catch (Exception $e) {
  log_event("frontend", "error", "Error connecting to RMQ for recentBooks: " . ($e->getMessage()));

  echo "<p style='color:red;'>Error loading featured books: " . htmlspecialchars($e->getMessage()) . "</p>";
}


?>