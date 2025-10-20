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
/* CHANGE api_cache TO library_cache AFTER MIDTERMS */
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

  $check_cache = $mysqli->prepare("SELECT response_json, last_updated FROM api_cache WHERE search_type=? AND query=? AND expires_at > NOW()LIMIT 1");
  $check_cache->bind_param("ss", $type, $query);
  $check_cache->execute();
  $cache_result = $check_cache->get_result();

  if ($cache_result->num_rows > 0) {
    $row = $cache_result->fetch_assoc();

    $age = time() - strtotime($row['last_updated']);
    $maxAge = 86400; // 24 hours

    if ($age < $maxAge) {
      echo "Cache HIT for {$type}={$query}\n";
      $cachedData = json_decode($row['response_json'], true);
      return ['status' => 'success', 'data' => $cachedData];
      // RETURNES CACHED DATA !!!! AKA CACHE HIT !!!!!
    } else {
      echo "Cache EXPIRED for {$type}={$query}\n";
      // nothing returned, so it considers it a miss
    }
  }


  // CACHE MISS !!!!!
  $base = "https://openlibrary.org/search.json";
  $encodedQuery = urlencode($query); // url encodes query when its actually getting sent to the API
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
  echo "Response length: " . strlen($response) . "\n"; // debugging
  curl_close($curl_api);

  $data = json_decode($response, true); // true is for the associative arrays. if false, it returns the json objects into objects.

  // api returns data as json (uses mongodb or non-relational db format)
  // reminder : mongodb "collection" is equivalent to a table. || "document" is one RECORD in a collection, stored as JSON objects
  // each attribute can have several data


  if (empty($data['docs']))
    return ['status' => 'fail', 'message' => 'no results']; // no results found

  $results = [];
  foreach ($data['docs'] as $book) {
    $results[] = [
      // NOTE : within each doc, attributes have nested information within an array 
      'title' => $book['title'] ?? 'Unknown title',
      'subtitle' => $book['subtitle'] ?? null,
      'alternative_title' => $book['alternative_title'] ?? null,
      'alternative_subtitle' => $book['alternative_subtitle'] ?? null,
      'author' => $book['author_name'][0] ?? 'Unknown author',
      'isbn' => $book['isbn'][0] ?? null,
      'publisher' => $book['publisher'][0] ?? null,
      'publish_year' => $book['publish_year'][0] ?? null,
      'ratings_count' => $book['ratings_count'] ?? null,

      // SUBJECT/GENRE INFO
      'subject_key' => $book['subject_key'] ?? [],
      'person_key' => $book['person_key'] ?? [],
      'place_key' => $book['place_key'] ?? [],
      'time_key' => $book['time_key'] ?? [],

      'year' => $book['first_publish_year'] ?? 'N/A'
    ];
  } // ANOTHER NOTE !! api_cache stores ALL of this under ONE COLUMN as a JSON type. still undecided on which would be best idk ugh




  // upsert?/insert? results into the cache db table !!!!!!
  $response_json = json_encode($results, JSON_UNESCAPED_UNICODE); // JSON_UNESCAPED_UNICODE --> prevents errors, ensures data integrity with special chars
  $insert = $mysqli->prepare("
    INSERT INTO api_cache (search_type, query, response_json)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE response_json=VALUES(response_json), last_updated=CURRENT_TIMESTAMP
  ");

  echo "Saving to cache: type={$type}, query='{$query}'\n"; // debugging

  $insert->bind_param("sss", $type, $query, $response_json);
  $insert->execute();

  echo "Cache MISS (fetched + saved) for {$type}={$query}\n";

  return ['status' => 'success', 'data' => $results];
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

// multi-queue capable version of the queue

// uses pcntl_fork -->  https://www.php.net/manual/en/function.pcntl-fork.php
$which = $argv[1] ?? 'all';
$iniPath = __DIR__ . "/rmqAccess.ini";

if ($which === 'all') { // to run all queues for DB and RMQ connection
  echo "Auth server starting for ALL queues...\n";
  $sections = ['LibrarySearch', 'LibraryDetails', 'LibraryCollect'];

  foreach ($sections as $section) {
    $pid = pcntl_fork(); // process control fork; creats child process 
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
