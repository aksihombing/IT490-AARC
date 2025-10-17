#!/usr/bin/php
<?php
// rmq script to stay running and listen for messages. listener
// when it receives a message, should talk to sql and send back a result
// uses cURL for GET, POST, etc.. requests to/from API.
// IT302 uses Postman but with UI
// HUGE WORK IN PROGRESS !!!

require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/get_host_info.inc';



function doBookSearch(array $req)
{
  /*
 The response with be of the following format. (taken from OpenLibrary)

{
    "start": 0,
    "num_found": 629,
    "docs": [
        {...},
        {...},
        ...
        {...}]
}
Each document specified listed in "docs" will be of the following format:

{
    "cover_i": 258027,
    "has_fulltext": true,
    "edition_count": 120,
    "title": "The Lord of the Rings",
    "author_name": [
        "J. R. R. Tolkien"
    ],
    "first_publish_year": 1954,
    "key": "OL27448W",
    "ia": [
        "returnofking00tolk_1",
        "lordofrings00tolk_1",
        "lordofrings00tolk_0",
    ],
    "author_key": [
        "OL26320A"
    ],
    "public_scan_b": true
  }
  */


  $type = $req['searchType'] ?? 'title'; // to search by title
  $query = urlencode($req['query'] ?? '');
  if ($query === '') return ['status' => 'fail', 'message' => 'missing query'];

  $base = "https://openlibrary.org/search.json";
  if ($type === 'author') {
    $url = "{$base}?author={$query}&limit=5";
  } else {
    $url = "{$base}?q={$query}&limit=5";
  }



  //https://www.php.net/manual/en/function.curl-setopt-array.php

  $curl_handle = curl_init(); // cURL -> client URL
  // cURL init --> creates cURL session


  curl_setopt_array($curl_handle, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true, // returns webpage
    CURLOPT_SSL_VERIFYPEER => true // verifies SSL
  ]);
  $response = curl_exec($curl_handle); // executes uRL
  if (curl_errno($curl_handle)) { // if cURL error :
    return ['status' => 'fail', 'message' => 'API error: ' . curl_error($curl_handle)];
  }
  curl_close($curl_handle);

  $data = json_decode($response, true); // true is for the associative arrays. if false, it returns the json objects into objects.

  // api returns data as json (uses mongodb or non-relational db format)
  // reminder : mongodb "collection" is equivalent to a table. || "document" is one RECORD in a collection, stored as JSON objects
  // each attribute can have several data


  if (empty($data['docs'])) return ['status' => 'fail', 'message' => 'no results'];

  $results = [];
  foreach ($data['docs'] as $book) {
    $results[] = [
      'title' => $book['title'] ?? 'Unknown title',
      'author' => $book['author_name'][0] ?? 'Unknown author',
      'year' => $book['first_publish_year'] ?? 'N/A'
    ];
  }

  return ['status' => 'success', 'data' => $results];
}





// ------------ Cache ------
function generateCacheKey($category, $identifier) {
    return strtolower(trim($category)) . ':' . strtolower(trim($identifier));
}

function getCache($category, $identifier, $ttl = 86400) {
    $conn = db();
    $cacheKey = generateCacheKey($category, $identifier);
    $stmt = $conn->prepare("SELECT data, UNIX_TIMESTAMP(last_updated) AS ts FROM api_cache WHERE cache_key=?");
    $stmt->bind_param("s", $cacheKey);
    $stmt->execute();
    $stmt->bind_result($data, $ts);

    if ($stmt->fetch()) {
        if ((time() - $ts) < $ttl) {
            return json_decode($data, true);
        }
    }
    return null; // expired or missing
}

function saveCache($category, $identifier, $data) {
    $conn = db();
    $cacheKey = generateCacheKey($category, $identifier);
    $json = json_encode($data);
    $stmt = $conn->prepare("
        INSERT INTO api_cache (cache_key, data)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE data=?, last_updated=NOW()
    ");
    $stmt->bind_param("sss", $cacheKey, $json, $json);
    $stmt->execute();
}




function requestProcessor($req)
{
  if (!isset($req['type'])) {
    return ['status' => 'fail', 'message' => 'no type'];
  }

  switch ($req['type']) {
    case 'book_search':
      return doBookSearch($req);
    default:
      return ['status' => 'fail', 'message' => 'Unknown request type'];
  }
}




// server logic ----------------

echo "API/DB server starting…\n";

// creates a server per each queue section in the rmqAccess.ini
$servers = [
  new rabbitMQServer(__DIR__ . "/rmqAccess.ini", "LibrarySearch"),
];

// child process for each queue so they can listen at the same time
pcntl_async_signals(true);
$children = [];

foreach ($servers as $srv) {
  $pid = pcntl_fork();

  // child process runs the server
  if ($pid === 0) {
    $srv->process_requests('requestProcessor');
    exit(0);
  }

  $children[] = $pid;
}

echo "LibrarySearch server running (" . count($children) . " workers)…\n";

// parent process just waits forever so children stay alive
while (true) {
  sleep(5);
}
