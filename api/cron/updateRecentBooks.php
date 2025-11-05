#!/usr/bin/php
<?php

/* 
UPDATE recentBooks VIA CRONTAB (updates 9:00):

* /5 * * * * /usr/bin/php /home/rea-sihombing/Project/IT490-AARC/api/updateRecentBooks.php >> /var/log/updateRecentBooks.log



to force-run the script :
/usr/bin/php /home/rea-sihombing/Project/IT490-AARC/api/updateRecentBooks.php


*/

// db config
$host = 'localhost';
$user = 'apiAdmin';
$pass = 'aarc490';
$name = 'apidb';


function curl_get(string $url)
{ // curl_get helper
    //https://www.php.net/manual/en/function.curl-setopt-array.php
    $curl_handle = curl_init($url);
    curl_setopt_array($curl_handle, [
        CURLOPT_RETURNTRANSFER => true, // returns webpage
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true, // verifies SSL
    ]);
    $curl_response = curl_exec($curl_handle);

    // error handling based on curl error number (forgot to add the links here)
    // https://www.php.net/manual/en/function.curl-errno.php
    // https://www.php.net/manual/en/function.curl-error.php
    // https://stackoverflow.com/questions/3987006/how-to-catch-curl-errors-in-php 
    if (curl_errno($curl_handle)) {
        $error = curl_error($curl_handle);
        curl_close($curl_handle);
        error_log("curl_get error for {$url}: {$error}");
        return false;
    }

    curl_close($curl_handle);
    return $curl_response;
}


try {

    $conn = new mysqli($host, $user, $pass, $name); // connect to local db 
    if ($conn->connect_errno) {
        throw new RuntimeException("DB connect failed: " . $conn->connect_error);
    }

    echo "Clearing Recent Books cache...\n";
    $conn->query("DELETE FROM recentBooks");


    // COPIED FROM Library_API.php

    $currentYear = date('Y');
    $searchByNew = "https://openlibrary.org/search.json?q=*&first_publish_year={$currentYear}&limit=10&sort=new"; //

// EXAMPLE https://openlibrary.org/search.json?q=*&first_publish_year=2022&limit=10&sort=new

    $search_response = curl_get($searchByNew);
    $search_data = json_decode($search_response, true);

    if (empty($search_data['docs']))
        return ['status' => 'fail', 'message' => 'no results']; // no results found


    $insertToTable = $conn->prepare("
    INSERT INTO recentBooks (
      olid, title, subtitle, author, isbn,
      book_desc, publish_year, ratings_average, ratings_count,
      subjects, person_key, place_key, time_key, cover_url
    )
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
  ");



    foreach ($search_data['docs'] as $book) { // FOREACH BOOK START
        // reading each doc that was returned
        $olid = str_replace('/works/', '', $book['key'] ?? null); //string --> cover_edition_key was specific to the edition of a book and not the actual OLID value in /works/
        $title = $book['title'] ?? 'Unknown title'; //string
        $subtitle = $book['subtitle'] ?? null; //string
        $author = $book['author_name'][0] ?? 'Unknown author'; //string


        $publish_year = $book['first_publish_year'] ?? null;
        // sanitize publish_year because books returned using q=*&sort=new have the year 9000+ on them for some reason.
        if (($publish_year) > (int)date('Y')) { 
            continue; // skip junk data
        }

        $cover_url = !empty($book['cover_i'])
            ? "https://covers.openlibrary.org/b/id/" . $book['cover_i'] . "-L.jpg" : null;


        // data from /works/{OLID}.json ----------------------

        $work_url = "https://openlibrary.org/works/{$olid}.json";
        $work_json = curl_get($work_url);

        $book_desc = '';
        $subjects = null;
        $person_key = null;
        $place_key = null;
        $time_key = null;

        if ($work_json) {
            $work_data = json_decode($work_json, true); // decode to read all data


            $desc_check = $work_data['description'] ?? null;
            if (is_array($desc_check)) {
                $book_desc = $desc_check['value'];
            } // some books have an array for description
            elseif (is_string($desc_check)) {
                $book_desc = $desc_check;
            } else {
                $book_desc = null;
            }

            // need to encode the json because the database column is of JSON type
            $subjects = json_encode($work_data['subjects'] ?? []);
            $person_key = json_encode($work_data['subject_people'] ?? []);
            $place_key = json_encode($work_data['subject_places'] ?? []);
            $time_key = json_encode($work_data['subject_times'] ?? []);
        }


        // data from /works/{OLID}/editions.json ----------------------

        $editions_url = "https://openlibrary.org/works/{$olid}/editions.json?limit=1"; // only get 1 of the editions isbn
        $editions_json = curl_get($editions_url);

        if ($editions_json) {
            $editions_data = json_decode($editions_json, true);
            $first_entry = $editions_data['entries'][0]; // gets the first entry result bc it will have ALL editions with different isbns listed. this is good enough for now, but it might be good to have a list of isbns.
            if (!empty($first_entry['isbn_13'][0])) {
                $isbn = $first_entry['isbn_13'][0];
            } elseif (!empty($first_entry['isbn_10'][0])) {
                $isbn = $first_entry['isbn_10'][0];
            } else {
                $isbn = null; // no isbn found
            }
        }


        // data from /works/{OLID}/ratings.json ----------------------

        $ratings_average = null;
        $ratings_count = null;
        $ratings_url = "https://openlibrary.org/works/{$olid}/ratings.json";
        $ratings_json = curl_get($ratings_url);
        if ($ratings_json) {
            $ratings_data = json_decode($ratings_json, true);
            $ratings_average = $ratings_data['summary']['average'] ?? null;
            $ratings_count = $ratings_data['summary']['count'] ?? null;
        }


        // INSERT INTO TABLE ON CACHE MISS ! ----------------------------------

        echo "Saving to cache: title={$title}, author='{$author}'\n"; // debugging

        // binding params for such a big table... nightmare fuel for anyone who craves efficiency

        $insertToTable->bind_param(
            "ssssssidisssss",
            $olid, // string
            $title, // string
            $subtitle, // string
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
} 
catch (Exception $e) {
    error_log("Unable to update recentBooks table : " . $e->getMessage());
    exit(1);
}


$insertToTable->close();
$conn->close();

?>


