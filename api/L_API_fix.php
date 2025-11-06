<?php

// database connection
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


// helper function
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
/* 
/works/olid.json    
/works/olid/editions.json   
/search.json   
/works/olid/ratings.json    
/subjects.json
*/

// -------------- What needs to be broken up --------------
// keep in separate script file for easier updating

// /search.json?q=XYZ&fields=x,y,z&limit=1





// /works/olid.json?





// /works/olid/editions.json?
function editionsEndpoint(array $req)
{ // need olid
  $editions_url = "https://openlibrary.org/works/{$olid}/editions.json?limit=1"; // only get 1 of the editions isbn
  $editions_json = curl_get($editions_url);

  if ($editions_json) {
    $editions_data = json_decode($editions_json, true);
    $first_entry = $editions_data['entries'][0]; // gets the first entry result

    if (!empty($first_entry['isbn_13'][0])) {
      $isbn = $first_entry['isbn_13'][0];
    } elseif (!empty($first_entry['isbn_10'][0])) {
      $isbn = $first_entry['isbn_10'][0];
    } else {
      $isbn = null; // no isbn found
    }
  }
}




// /works/olid/ratings.json?





// /subject.json
function subjectEndpoint(array $req)
{
  $work_url = "https://openlibrary.org/works/{$olid}.json";
  $work_json = curl_get($work_url);

  $book_desc = 'No book description available';
  $subjects = null;
  $person_key = null;
  $place_key = null;
  $time_key = null;

  if ($work_json) {
    $work_data = json_decode($work_json, true); // decode to read all data


    if (is_array($work_data['description'])) { // still getting a php warning idky
      $book_desc = $work_data['description']['value'];
    } elseif (is_string($work_data['description'])) {
      $book_desc = $work_data['description'];
    } else {
      $book_desc = 'No book description available';
    }

    // need to encode the json because the database column is of JSON type
    $subjects = json_encode(array_slice($work_data['subjects'] ?? [], 0, 20)); // take the first 20 subjects max
    $person_key = json_encode(array_slice($work_data['subject_people'] ?? [], 0, 20));
    $place_key = json_encode(array_slice($work_data['subject_places'] ?? [], 0, 20));
    $time_key = json_encode(array_slice($work_data['subject_times'] ?? [], 0, 20));

    // DEBUGGING
    //var_dump($subjects);
    //var_dump($person_key);
    //var_dump($place_key);
    //var_dump($time_key);
  }
}




// bookCache(array $data)
function doBookCache(array $req)
{
  $type = $req['searchType'] ?? 'title';
  $query = strtolower(trim($req['query'] ?? ''));
  if ($query === '')
    return ['status' => 'fail', 'message' => 'missing query'];

  $limit = isset($req['limit']) && is_numeric($req['limit']) ? $req['limit'] : 10;
  $page = isset($req['page']) ? intval($req['page']) : 1;

  // CACHE CHECK ----------------------------------
  $mysqli = db();

  echo "Checking cache for: type={$type}, query='{$query}', limit={$limit}, page={$page}\n";

  $check_cache = $mysqli->prepare("SELECT * FROM library_cache WHERE search_type=? AND query=? AND pageNum=? AND expires_at > NOW() LIMIT ?");
  $check_cache->bind_param("ssii", $type, $query, $page, $limit);
  $check_cache->execute();
  $cache_result = $check_cache->get_result();

  if ($cache_result->num_rows > 0) {
    echo "Cache HIT for {$type}={$query}\n";
    $cachedData = [];

    while ($row = $cache_result->fetch_assoc()) {
      $cachedData[] = $row;
    }

    return ['status' => 'success', 'data' => $cachedData];
    // return cache HIT
  }
}

// doBookSearch ()
// use search.json




// doBookRecommend ()
/*// RECOMMENDATION SYSTEM
// multiply the weight of the genre based on the books they have that of
// i.e. if they have a lot of books with the same genre in their library, the recommendation should reflect whatever genre weights heaviest; can assign counts
//1 - negative weight
//5 - positive weight
*/





// doBookDetails () 
// combines all endpoints for accurate info





// getRecentBooks ()











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
      return doBookSearch($req); // check api db cache before calling api

    case 'recent_books':
      return getRecentBooks(); // pull information from recentBooks, which uses CRON to auto-update table

    case 'book_details':
      return doBookDetails($req); // does not store into api cache, calls the details in-the-moment from the api

    case 'book_recommend':
      return doBookRecommend($req);

    case 'book_collect':
      return doBookCollect($req); // not sure if needed

    default:
      return ['status' => 'fail', 'message' => 'unknown type'];
  }
}

echo "API/DMZ server starting…\n";
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

?>