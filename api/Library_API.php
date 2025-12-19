<?php
require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/get_host_info.inc';

require_once __DIR__ . '/api_process.php';
require_once __DIR__ . '/log_producer.php';

// api_process requires api_cache and api_endpoints

// ---------------- SERVER ----------------

function requestProcessor($req)
{
  echo "----------------------\n";
  echo "Received request:\n";
  var_dump($req);
  flush();

  if (!isset($req['type'])) {
    return ['status' => 'fail', 'message' => 'no type'];
  }

  switch ($req['type']) {
    // [type] references api_process.php
    // api_process.php references api_endpoints.php
    case 'api_book_search':
      return doBookSearch($req); // check api db cache before calling api

    case 'api_book_details':
      return doBookDetails($req); // does not store into api cache, calls the details in-the-moment from the api

    case 'book_recommend':
      return doBookRecommend($req);


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
  $sections = ['LibraryCollect'];

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
  log_event('dmz', 'warning', 'Auth server stopped for {$which}');
  echo "Auth server stopped for {$which}\n";
}

?>