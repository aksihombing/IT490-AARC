<?php
require_once(__DIR__ . '/../rabbitMQ/rabbitMQLib.inc');
//session_start();

$bookSearchResults = []; // update results while doBookSearch loop
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
                'query' => $query,
                'limit' => 10,
                'page' => 1
            ];

            $response = $client->send_request($request);
            //var_dump($response); //debugging 

            if ($response['status'] === 'success') {
                $bookSearchResults = $response['data'];
            } else {
                $error = $response['message'] ?? 'Unknown error from server.';
            }
        } catch (Exception $e) {
            $error = "Error connecting to search service: " . $e->getMessage();
        }
    }
}
?>