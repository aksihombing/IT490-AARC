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

  if (curl_errno($curl_handle)) {
    $error = curl_error($curl_handle);
    curl_close($curl_handle);
    error_log("curl_get error for {$url}: {$error}");
    return false;
  }

  curl_close($curl_handle);
  return $curl_response;
}


// DATABASE CACHE VER --- !!
function doBookSearch(array $req)
{
  $type = $req['searchType'] ?? 'title'; // to search by title
  $query = strtolower(trim($req['query'] ?? ''));
  if ($query === '')
    return ['status' => 'fail', 'message' => 'missing query'];

  // CACHE CHECK ----------------------------------
  $mysqli = db();

  echo "Checking cache for: type={$type}, query='{$query}'\n"; //debugging

  $check_cache = $mysqli->prepare("SELECT * FROM library_cache WHERE search_type=? AND query=? AND expires_at > NOW() LIMIT 10"); // might need to change limit ? idk
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
    // RETURNS CACHED DATA !!!! AKA CACHE HIT !!!!!
  }





  // FIND DATA ON CACHE MISS ----------------------------------

  // for search.json!!!  ----------------------
  $base = "https://openlibrary.org/search.json"; //base url for endpoint
  $encodedQuery = urlencode($query); // url encodes query when its actually getting sent to the API
  if ($type === 'author') {
    $searchurl = "{$base}?author={$encodedQuery}&limit=5"; //limiting results to 5 for midterm idc
  } else {
    $searchurl = "{$base}?q={$encodedQuery}&limit=5";
  } // debating on whether the query type should be stored? ill leave it for now, but SUBJECT TO CHANGE !

  $search_response = curl_get($searchurl);
  $curl_data = json_decode($search_response, true); // true is for the associative arrays. if false, it returns the json objects into objects. make sure to decode the response from the api before upserting/ inserting it back into the db

  if (empty($curl_data['docs']))
    return ['status' => 'fail', 'message' => 'no results']; // no results found


  foreach ($curl_data['docs'] as $book) { // reading each doc that was returned
    $olid = $book['cover_edition_key'] ?? null; //string, not sure if i should save the ['key'] too?
    $title = $book['title'] ?? 'Unknown title'; //string
    $subtitle = $book['subtitle'] ?? null; //string
    $author = $book['author_name'][0] ?? 'Unknown author'; //string
    $publish_year = $book['first_publish_year'];
    $cover_url = !empty($book['cover_i'])
      ? "https://covers.openlibrary.org/b/id/" . $book['cover_i'] . "-L.jpg" : null; // ternary -> if cover_i is set, then it saves the link
    // cover_i only saves the id of where the cover is, so we have to build the link manually


    // data from /works/{OLID}.json ----------------------

    $work_url = "https://openlibrary.org/works/{$olid}.json";
    $work_json = curl_get($work_url);

    $book_desc = '';
    $subjects = null;
    $person_key = null;
    $place_key = null;
    $time_key = null;

    if ($work_json) {
      $work_data = json_decode($work_json, true);

      $book_desc = $work_data['description'] ?? ''; // do i need to encode this?
      $subjects = $work_data['subjects'] ?? [];
      $person_key = $work_data['subject_people'] ?? [];
      $place_key = $work_data['subject_places'] ?? [];
      $time_key = $work_data['subject_times'] ?? [];
    }


    // data from /works/{OLID}/editions.json ----------------------

    $isbn = null;
    $editions_url = "https://openlibrary.org/works/{$olid}/editions.json?limit=1"; // only get 1 of the editions isbn
    $editions_json = curl_get($editions_url);

    if ($editions_json) {
      $editions_data = json_decode($editions_json, true);
      $isbn = $editions_data['editions']['isbn_13'][0];
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

  }



  // need to make sure all data is in order
  $insertToTable = $mysqli->prepare( "
    INSERT INTO library_cache (
      search_type, query, olid, title, subtitle, author, isbn,
      book_desc, publish_year, ratings_average, ratings_count,
      subjects, person_key, place_key, time_key, cover_url
    )
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
      title=VALUES(title),
      subtitle=VALUES(subtitle),
      author=VALUES(author),
      isbn=VALUES(isbn),
      book_desc=VALUES(book_desc),
      publish_year=VALUES(publish_year),
      ratings_average=VALUES(ratings_average),
      ratings_count=VALUES(ratings_count),
      subjects=VALUES(subjects),
      person_key=VALUES(person_key),
      place_key=VALUES(place_key),
      time_key=VALUES(time_key),
      cover_url=VALUES(cover_url),
      last_updated=CURRENT_TIMESTAMP
  " );
  // ** need to look into if last_updated is updated correctly ?

  // https://github.com/internetarchive/openlibrary/blob/b4afa14b0981ae1785c26c71908af99b879fa975/openlibrary/plugins/worksearch/schemes/works.py#L119-L153


  // INSERT INTO TABLE ON CACHE MISS ! ----------------------------------

  echo "Saving to cache: type={$type}, query='{$query}'\n"; // debugging

  // binding params for such a big table... nightmare fuel for anyone who craves efficiency
  $searchbookresults = []; // empty array to get sent to the webserver, idk wehre it should actually go


  $insertToTable->bind_param(
    "ssssssssiiisssss",
    $type, // string
    $query, // string
    $olid, // string
    $title, // string
    $subtitle, // string
    $author, // string
    $isbn, // string
    $book_desc, // string
    $publish_year, // int
    $ratings_average, //int
    $ratings_count, // int
    $subjects, // string
    $person_key, // string
    $place_key, // string
    $time_key, // string
    $cover_url // string
  ); // need to check if this was done correctly bc its just TOOO much

  $insertToTable->execute();

  $searchbookresults[] = [ // this gets returns to the webserver
    'olid' => $olid,
    'title' => $title,
    'subtitle' => $subtitle,
    'author' => $author,
    'isbn' => $isbn,
    'book_desc' => $book_desc,
    'publish_year' => $publish_year,
    'ratings_average' => $ratings_average,
    'ratings_count' => $ratings_count,
    'subjects' => $subjects,
    'cover_url' => $cover_url
  ];


  echo "Cache MISS (fetched + saved) for {$type}={$query}\n";

  return ['status' => 'success', 'data' => $searchbookresults];
}


// Cache Tables Pre-Populated via cron
function getRecentBooks()
{
  $mysqli = db();
  $result = $mysqli->query("SELECT title, author, year, cover_url FROM recentBooks ORDER BY year DESC LIMIT 10"); // will return 10 results

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
