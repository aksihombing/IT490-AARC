#!/usr/bin/php
<?php
require_once __DIR__ . '/rabbitMQLib.inc';
/* 
set file/folder permissions:

sudo chown -R aida:aida /home/aida/cron



UPDATE recentBooks VIA CRONTAB (updates 10:00 AM):

0 10 * * * /usr/bin/php /home/aida/cron/updateRecentBooks.php >> /home/aida/cron/recentBooks.log.txt 2>&1
// logs both stdout and stderr aka EVERYTHING

0 10 * * * /usr/bin/php /home/aida/cron/updateRecentBooks.php >> /home/aida/cron/recentBooks.log.txt 2>/dev/null
// surpresses errors ? not sure which is needed



to force-run the script :
/usr/bin/php /home/aida/cron/updateRecentBooks.php > /dev/null 2>&1


*/

// apidb config
$host = 'localhost';
$user = 'apiAdmin';
$pass = 'aarc490';
$name = 'apidb';

date_default_timezone_set('America/New_York');

try {

    echo ("------------------------");
    echo date ('Y-m-d H:i');
    $conn = new mysqli($host, $user, $pass, $name); // connect to local db 
    if ($conn->connect_errno) {
        throw new RuntimeException("DB connect failed: " . $conn->connect_error);
    }

    echo "Clearing Recent Books cache...\n";
    $conn->query("DELETE FROM recentBooks");


    // COPIED FROM Library_API.php

    $currentYear = date('Y');
    $searchByNewQuery = "first_publish_year:{$currentYear}";

    $request = [
        'type' => 'api_book_search',
        'query' => $searchByNewQuery,
        'limit' => 10
    ];

    echo "Building request...\n";
    $client = new rabbitMQClient(__DIR__ . "/library.ini", "LibraryCollect");
    $response = $client->send_request($request);
    
    echo "Sending request to DMZ lister...\n";

    if ($response['status'] != 'success' || empty($response['data'])) {
        error_log("Failed to gather books from DMZ");
    }

    $books = $response['data'];

    $insertToTable = $conn->prepare("
    INSERT INTO recentBooks (
      olid, title, author, isbn,
      book_desc, publish_year, ratings_average, ratings_count,
      subjects, person_key, place_key, time_key, cover_url
    )
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
  ");



    foreach ($books as $book) { // FOREACH BOOK START
        $olid = $book['olid'] ?? null; // string
        $title = $book['title'] ?? 'Unknown title'; // string
        $author = $book['author'][0] ?? 'Unknown author'; //string
        $isbn = $book['isbn'][0] ?? [];
        $book_desc = $work_data['book_desc'];
        $publish_year = $book['publish_year'] ?? []; // string
        $ratings_average = $book['ratings_average'] ?? [];
        $ratings_count = $book['ratings_count'] ?? [];
        $subjects = json_encode($book['subject_key'] ?? []);
        $person_key = json_encode($book['person_key'] ?? []);
        $place_key = json_encode($book['place_key'] ?? []);
        $time_key = json_encode($book['time_key'] ?? []);

        $cover_url = $book['cover_url'] ?? null;


        // INSERT INTO TABLE ON CACHE MISS ! ----------------------------------

        echo "Saving to cache: title={$title}, author='{$author}'\n"; // debugging

        // binding params for such a big table... nightmare fuel for anyone who craves efficiency

        $insertToTable->bind_param(
            "sssssidisssss",
            $olid, // string
            $title, // string
            $author, // string
            $isbn, // string
            $book_desc, // string
            $publish_year, // int
            $ratings_average, // decimal (double)
            $ratings_count, // int
            $subjects, // json_encode(array)
            $person_key, // json_encode(array)
            $place_key, // json_encode(array)
            $time_key, // json_encode(array)
            $cover_url // string
        ); // need to check if this was done correctly bc its just TOOO much

        echo "Added {$title} = {$author}\n";
        $insertToTable->execute();
    } // FOREACH BOOK END
    echo "All books updated";
} catch (Exception $e) {
    error_log("Unable to update recentBooks table : " . $e->getMessage());
    exit(1);
}


$insertToTable->close();
$conn->close();

?>