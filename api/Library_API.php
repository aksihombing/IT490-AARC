#!/usr/bin/php
<?php
// rmq script to stay running and listen for messages. listener
// when it receives a message, it should check the local apidb, request from the API itself if the query is not populated yet in the db, and then send back to the frontend

// uses cURL for GET, POST, etc.. requests to/from API.
// https://stackoverflow.com/questions/9802788/call-a-rest-api-in-php
// https://weichie.com/blog/curl-api-calls-with-php/

// HUGE WORK IN PROGRESS !!!

require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/get_host_info.inc';


// connects to the local sql database
/* CHANGE library_cache TO library_cache AFTER MIDTERMS */
function db()
{
  $host = 'localhost';
  $user = 'apiAdmin';
  $pass = 'aarc490';
  $name = 'apidb';

  $mysqli = new mysqli($host, $user, $pass, $name);
  if ($mysqli->connect_errno) {
    throw new RuntimeException("DB connect failed: " . $mysqli->connect_error);
  }
  return $mysqli;
}


// api listener processing functions


// DATABASE CACHE VER --- !!
function doBookSearch(array $req)
{
  $type = $req['searchType'] ?? 'title'; // to search by title
  $query = strtolower(trim($req['query'] ?? ''));
  if ($query === '')
    return ['status' => 'fail', 'message' => 'missing query'];

  // library_cache check !!!!
  $mysqli = db();

  echo "Checking cache for: type={$type}, query='{$query}'\n"; //debugging

  $check_cache = $mysqli->prepare("SELECT * FROM library_cache WHERE search_type=? AND query=? AND expires_at > NOW() LIMIT 1"); // might need to change limit ? idk
  $check_cache->bind_param("ss", $type, $query);
  $check_cache->execute();
  $cache_result = $check_cache->get_result();

  if ($cache_result->num_rows > 0) { // if there is 1 or more results
      echo "Cache HIT for {$type}={$query}\n";
      $cachedData = [];

      while ($row = $cache_result->fetch_assoc()) {
        $cachedData[] = $row;
      }

      return ['status' => 'success', 'data' => $cachedData];
      // RETURNES CACHED DATA !!!! AKA CACHE HIT !!!!!
    }


  // CACHE MISS !!!!!
  $base = "https://openlibrary.org/search.json"; //base url for endpoint
  $encodedQuery = urlencode($query); // url encodes query when its actually getting sent to the API
  // need to decode it when its actually stored in db to remove + and other symbols
  if ($type === 'author') {
    $url = "{$base}?author={$encodedQuery}&limit=5"; //limiting results to 5 for midterm idc
  } else {
    $url = "{$base}?q={$encodedQuery}&limit=5";
  }

  //https://www.php.net/manual/en/function.curl-setopt-array.php

  $curl_api = curl_init(); // cURL -> client URL
  // cURL init --> creates cURL session

  curl_setopt_array($curl_api, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true, // returns webpage
    CURLOPT_SSL_VERIFYPEER => true // verifies SSL
  ]);

  echo "Fetching: $url\n"; //debugging hanging request
  $response = curl_exec($curl_api); // executes uRL
  if (curl_errno($curl_api)) { // if cURL error :
    return ['status' => 'fail', 'message' => 'API error: ' . curl_error($curl_api)];
  }
  //echo "Response length: " . strlen($response) . "\n"; // debugging
  curl_close($curl_api);

  $curl_data = json_decode($response, true); // true is for the associative arrays. if false, it returns the json objects into objects. make sure to decode the response from the api before upserting? inserting? it back into the db

  // api returns data as json (uses mongodb or non-relational db format)
  // reminder : mongodb "collection" is equivalent to a table. || "document" is one RECORD in a collection, stored as JSON objects
  // each FIELD can have several data


  if (empty($curl_data['docs']))
    return ['status' => 'fail', 'message' => 'no results']; // no results found

  //omg ... 17 ?
  $insert = $mysqli->prepare("
    INSERT INTO library_cache (
      search_type, query, olid, title, subtitle, 
      alternative_title, alternative_subtitle,
      author, isbn, publisher, publish_year, ratings_count,
      subject_key, person_key, place_key, time_key, cover_url
    )
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) 
          ON DUPLICATE KEY UPDATE
          title=VALUES(title),
          subtitle=VALUES(subtitle),
          alternative_title=VALUES(alternative_title),
          alternative_subtitle=VALUES(alternative_subtitle),
          author=VALUES(author),
          isbn=VALUES(isbn),
          publisher=VALUES(publisher),
          publish_year=VALUES(publish_year),
          ratings_count=VALUES(ratings_count),
          subject_key=VALUES(subject_key),
          person_key=VALUES(person_key),
          place_key=VALUES(place_key),
          time_key=VALUES(time_key),
          cover_url=VALUES(cover_url),
          last_updated=CURRENT_TIMESTAMP
    "); // this looks horrible but need to make sure all data is updated when the key is duplicated
    // ** need to look into if last_updated is updated correctly ?

  // https://github.com/internetarchive/openlibrary/blob/b4afa14b0981ae1785c26c71908af99b879fa975/openlibrary/plugins/worksearch/schemes/works.py#L119-L153

  foreach ($curl_data['docs'] as $book) {
      // NOTE : within each doc, attributes have nested information within an array 
      $olid = $book['cover_edition_key'] ?? null; //string
      $title = $book['title'] ?? 'Unknown title'; //string
      $subtitle = $book['subtitle'] ?? null; //string
      $alternative_title = $book['alternative_title'] ?? null; //string
      $alternative_subtitle = $book['alternative_subtitle'] ?? null;//string
      $author = $book['author_name'][0] ?? 'Unknown author'; //string
      $isbn = $book['isbn'][0] ?? null; //string
      $publisher = $book['publisher'][0] ?? null; //string
      $publish_year = $book['publish_year'][0] ?? null; //int
      $ratings_count = $book['ratings_count'] ?? null; //int

      // SUBJECT/GENRE INFO
      $subject_key = json_encode($book['subject_key'] ?? []); //string technically?
      $person_key = json_encode($book['person_key'] ?? []); //string 
      $place_key = json_encode($book['place_key'] ?? []); //string 
      $time_key = json_encode($book['time_key'] ?? []); //string 

      $cover_url = !empty($book['cover_i'])
      ? "https://covers.openlibrary.org/b/id/" . $book['cover_i'] . "-L.jpg": null; // ternary -> if cover_i is set, then it saves the link
  } // ANOTHER NOTE !! library_cache stores ALL of this under ONE COLUMN as a JSON type. still undecided on which would be best idk ugh --> nvm
  // https://stackoverflow.com/questions/5986745/json-column-vs-multiple-columns




  // upsert?/insert? results into the cache db table !!!!!!
 // $response_json = json_encode($results, JSON_UNESCAPED_UNICODE); // JSON_UNESCAPED_UNICODE --> prevents errors, ensures data integrity with special chars


  echo "Saving to cache: type={$type}, query='{$query}'\n"; // debugging

  // binding params for such a big table... nightmare fuel for anyone who craves efficiency
  $searchbookresults = []; // empty array to get sent to the webserver, idk wehre it should actually go

  
  $insert->bind_param(
    "sssssssssiissssss",
    $type, $query,
    $olid, $title, $subtitle,
    $alternative_title, $alternative_subtitle,
    $author, $isbn, $publisher, $publish_year, $ratings_count,
    $subject_key, $person_key, $place_key, $time_key, $cover_url
    ); // need to check if this was done correctly bc its just TOOO much

  $insert->execute();

    $searchbookresults[] = [ // this gets returns to the webserver
      'olid' => $olid,
      'title' => $title,
      'subtitle' => $subtitle,
      'author' => $author,
      'isbn' => $isbn,
      'publisher' => $publisher,
      'publish_year' => $publish_year,
      'ratings_count' => $ratings_count,
      'subject_key' => $subject_key,
      'person_key' => $person_key,
      'place_key' => $place_key,
      'time_key' => $time_key,
      'cover_url' => $cover_url
    ];


  echo "Cache MISS (fetched + saved) for {$type}={$query}\n";

  return ['status' => 'success', 'data' => $searchbookresults];
}


// Cache Tables Pre-Populated via cron
function getRecentBooks()
{
  $mysqli = db();
  $result = $mysqli->query("SELECT title, author, year, cover_url FROM recentBooks ORDER BY year DESC LIMIT 10");

  $books = [];
  while ($row = $result->fetch_assoc()) {
    $books[] = $row;
  }

  return ['status' => 'success', 'data' => $books];
}

// (( scrapped Popular_books ))







// ---------------- SERVER ----------------

// decides which function to run
function requestProcessor($req)
{
  echo "Received request:\n";
  var_dump($req);
  flush();

  if (!isset($req['type'])) {
    return ['status' => 'fail', 'message' => 'no type'];
  }

  switch ($req['type']) {
    case 'book_search':
      return doBookSearch($req);

    case 'recent_books':
      return getRecentBooks(); // not done yet idk im spiraling
      
    case 'book_details':
      return doBookDetails($req); // not sure if needed

    case 'book_collect':
      return doBookCollect($req); // not sure if needed

    default:
      return ['status' => 'fail', 'message' => 'unknown type'];
  }
}

echo "API/DB server starting…\n";
flush();

// multi-queue capable version of the queue from before

// uses pcntl_fork -->  https://www.php.net/manual/en/function.pcntl-fork.php
$which = $argv[1] ?? 'all';
$iniPath = __DIR__ . "/rmqAccess.ini";

if ($which === 'all') { // to run all queues for DB and RMQ connection
  echo "Auth server starting for ALL queues...\n";
  $sections = ['LibrarySearch', 'LibraryDetails', 'LibraryCollect'];

  foreach ($sections as $section) {
    $pid = pcntl_fork(); // process control fork; creates child process from parent process
    if ($pid == -1) {
      die("Failed to fork for {$section}\n");
    } elseif ($pid === 0) {
      // child process
      echo "Listening on {$section}\n";
      $server = new rabbitMQServer($iniPath, $section);
      $server->process_requests('requestProcessor');
      exit(0);
    }
  }

  // parent waits for all children
  while (pcntl_wait($status) > 0) {
  }
} else {
  echo "Auth server starting for queue section: {$which}\n";
  $server = new rabbitMQServer($iniPath, $which);
  echo "Connecting to queue: {$which}\n";
  flush();
  $server->process_requests('requestProcessor');
  echo "Auth server stopped for {$which}\n";
}
