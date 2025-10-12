#!/usr/bin/php
<?php
// rmq script to stay running and listen for messages. listener
// when it receives a message, should talk to sql and send back a result

require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/get_host_info.inc';

// connects to the local sql database
function db() {
  $host = '172.28.109.213'; // need local ip, NEED TO CHANGE
  $user = 'testUser'; // needdatabase user
  $pass = '12345'; // need database password
  $name = 'testdb'; // needdatabase name

  $mysqli = new mysqli($host, $user, $pass, $name);
  if ($mysqli->connect_errno) {
    throw new RuntimeException("DB connect failed: ".$mysqli->connect_error);
  }
  return $mysqli;
}

function doBookSearch($query) {
    require_once(__DIR__.'/api/book_api.inc.php'); 
    return callOpenLibrary($query);
}










function requestProcessor($req) {
  if (!isset($req['type'])) {
    return ['status'=>'fail','message'=>'no type'];
  }

  switch ($req['type']) {
    case 'book_search': return doBookSearch($req['query']);

  }
}




// server logic ----------------

echo "Auth server starting…\n";

// creates a server per each queue section in the host.ini
$servers = [
  new rabbitMQServer(__DIR__."/host.ini", "AuthRegister"),
  new rabbitMQServer(__DIR__."/host.ini", "AuthLogin"),
  new rabbitMQServer(__DIR__."/host.ini", "AuthValidate"),
  new rabbitMQServer(__DIR__."/host.ini", "AuthLogout"),
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

echo "Auth server running (" . count($children) . " workers)…\n";

// parent process just waits forever so children stay alive
while (true) {
  sleep(5);
}
