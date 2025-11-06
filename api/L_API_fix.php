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

  // error handling based on curl error number
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