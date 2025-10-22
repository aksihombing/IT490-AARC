#!/usr/bin/php
<?php

// should now work for all auth/library/club features

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);

// rmq script to stay running and listen for messages. db listener
// when it receives a message, should talk to sql and send back a result

require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/get_host_info.inc';


// connects to the local sql database
function db() {
  $host = 'localhost'; 
  $user = 'testUser'; 
  $pass = '12345';
  $name = 'testdb'; 

  $mysqli = new mysqli($host, $user, $pass, $name);
  if ($mysqli->connect_errno) {
    throw new RuntimeException("DB connect failed: ".$mysqli->connect_error);
  }
  return $mysqli;
}


// request handlers

// --- AUTHENTICATION ---
function doRegister(array $req) {
  $email = $req['email'] ?? '';
  $username = $req['username'] ?? '';
  $hash = $req['password'] ?? '';

// validate entered fields
  if ($email==='' || $username==='' || $hash==='') {
    return ['status'=>'fail','message'=>'missing fields'];
  }

  $conn = db();

// see if user already exists in db
  $stmt = $conn->prepare("SELECT id FROM users WHERE username=? OR emailAddress=?");
  $stmt->bind_param("ss", $username, $email);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
    return ['status'=>'fail','message'=>'user or email exists'];
  }
  $stmt->close();

// inserts new user into database
  $stmt = $conn->prepare("INSERT INTO users (username,emailAddress,password_hash) VALUES (?,?,?)");
  $stmt->bind_param("sss", $username, $email, $hash);
  if (!$stmt->execute()) {
    return ['status'=>'fail','message'=>'db insert failed'];
  }

  return ['status'=>'success'];
}

function doLogin(array $req) {
  $username = $req['username'] ?? '';
  $password = $req['password'] ?? '';

 if ($username==='' || $password==='') {
    return ['status'=>'fail','message'=>'missing fields'];
  }
  $conn = db();


  $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");

  if (!$stmt) {
        error_log("doLogin preparing SELECT failed: " . $conn->error);
        return ['status'=>'fail','message'=>'server error'];
    }
  
  $stmt->bind_param("s", $username);

  if (!$stmt->execute()) {
        error_log("doLogin execute SELECT failed: " . $stmt->error);
        return ['status'=>'fail','message'=>'server error'];
    }
  
  $stmt->store_result();

  if ($stmt->num_rows === 1) { 
    // checks if theres a row in the db with from the query result
    $stmt->bind_result($uid,$dbUser,$dbHash);
    $stmt->fetch();
    error_log("doLogin fetching user: uid={$uid}, username={$dbUser}");
    
    if (password_verify($password,$dbHash)){
     // create a session key, should be secure ?
      $session = bin2hex(random_bytes(32));
      $exp = (new DateTime('+7 days'))->format('Y-m-d H:i:s');
      error_log("doLogin password ok, generating session: key={$session}, expires={$exp}");

      // stores the session in the db. change variable name in case it caused problems
      $stmt2 = $conn->prepare("INSERT INTO sessions (user_id, session_key, expires_at) VALUES (?, ?, ?)");
            if (!$stmt2) {
                error_log("doLogin preparing insert into sessions failed: " . $conn->error);
            } else {
                $stmt2->bind_param("iss", $uid, $session, $exp);
                if (!$stmt2->execute()) {
                    error_log("doLogin execute insert into session failed: " . $stmt2->error);
                } else {
                    error_log("doLogin Session inserted for uid={$uid}, session_key={$session}");
                }
            }

            return [
                'status' => 'success',
                'uid' => $uid,
                'username' => $dbUser,
                'session_key' => $session
            ];
        } else {
            error_log("doLogin invalid password for user {$username}");
            return ['status'=>'fail', 'message'=>'invalid password'];
        }
    } else {
        error_log("doLogin user not found: {$username}");
        return ['status'=>'fail', 'message'=>'user not found'];
    }
}

function doValidate(array $req) {
  $sid = $req['session_key'] ?? '';
  if ($sid==='') return ['status'=>'fail','message'=>'missing session'];

  $conn = db();
  $stmt = $conn->prepare("
      SELECT u.id,u.username,u.emailAddress,s.expires_at
      FROM sessions s
      JOIN users u ON u.id=s.user_id
      WHERE s.session_key=? LIMIT 1
  ");
  $stmt->bind_param("s", $sid);
  $stmt->execute();
  $stmt->bind_result($uid,$uname,$email,$exp);

  if (!$stmt->fetch()) return ['status'=>'fail','message'=>'not found'];
  if ($exp && strtotime($exp) < time()) {
    // session is expired so deletes
    $del = $conn->prepare("DELETE FROM sessions WHERE session_key=?");
    $del->bind_param("s",$sid);
    $del->execute();
    return ['status'=>'fail','message'=>'expired'];
  }

  return ['status'=>'success','user'=>['id'=>$uid,'username'=>$uname,'email'=>$email]];
}

function doLogout(array $req) {
  $sid = $req['session_key'] ?? '';
  if ($sid==='') return ['status'=>'fail','message'=>'missing session'];

  $conn = db();
  $stmt = $conn->prepare("DELETE FROM sessions WHERE session_key=?");
  $stmt->bind_param("s",$sid);
  $stmt->execute();
  return ['status'=>'success'];
}

// --- MIDTERM GROUP DELIVERABLES (WEBSITE FEATURES) ---

// CHIZZY'S FUNCTIONS
//removes a book from user's library
function doLibraryRemove(array $req) {
  $uid  = (int)($req['user_id'] ?? 0);
  $work = $req['works_id'] ?? '';
  if (!$uid || $work === '') return ['status'=>'fail','message'=>'missing user_id or works_id'];

  $conn = db();
  $stmt = $conn->prepare("DELETE FROM user_library WHERE user_id=? AND works_id=? LIMIT 1");
  if (!$stmt) return ['status'=>'fail','message'=>'prep failed'];
  $stmt->bind_param("is", $uid, $work);
  if (!$stmt->execute()) return ['status'=>'fail','message'=>'execute failed'];

  return ($stmt->affected_rows > 0)
    ? ['status'=>'success']
    : ['status'=>'fail','message'=>'not found'];
}

//gets all the reviews for a specific book
function doReviewsList(array $req) {
  $works_id = trim($req['works_id'] ?? '');
  if ($works_id === '') return ['status'=>'fail','message'=>'missing works_id'];

  $conn = db();

  $stmt = $conn->prepare("
    SELECT r.id, r.user_id, u.username, r.rating, r.body, r.created_at
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.works_id = ?
    ORDER BY r.created_at DESC
    LIMIT 200
  ");
  $stmt->bind_param("s", $works_id);
  $stmt->execute();
  $res = $stmt->get_result();

  $items = [];
  while ($row = $res->fetch_assoc()) $items[] = $row;

  return [
    'status' => 'success',
    'items'  => $items
  ];
}

function doReviewsCreate(array $req) {
  $user_id  = (int)($req['user_id'] ?? 0);
  $works_id = trim($req['works_id'] ?? '');
  $rating   = (int)($req['rating'] ?? 0);
  $body     = trim($req['body'] ?? ($req['comment'] ?? ''));

  if ($user_id <= 0 || $works_id === '' || $rating < 1 || $rating > 5) {
    return ['status'=>'fail','message'=>'missing or invalid fields'];
  }

  $conn = db();

  $stmt = $conn->prepare("
    INSERT INTO reviews (user_id, works_id, rating, body)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE rating=VALUES(rating), body=VALUES(body), created_at=NOW()
  ");
  $stmt->bind_param("isis", $user_id, $works_id, $rating, $body);
  $ok = $stmt->execute();

  return $ok
    ? ['status'=>'success','message'=>'review saved']
    : ['status'=>'fail','message'=>'database error'];
}

function doLibraryList(array $req) {
  $user_id = (int)($req['user_id'] ?? 0);
  if ($user_id <= 0) {
    return ['status' => 'fail', 'message' => 'missing user_id'];
  }

  $conn = db();

  // Get books saved by this user
  $stmt = $conn->prepare("
    SELECT works_id
    FROM user_library
    WHERE user_id = ?
    ORDER BY added_at DESC
    LIMIT 200
  ");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $res = $stmt->get_result();

  $items = [];
  while ($row = $res->fetch_assoc()) {
    $items[] = [
      'works_id' => $row['works_id']
    ];
  }

  return [
    'status' => 'success',
    'items'  => $items
  ];
}

// adds a book to user's library, forgot to add this function earlier
function doLibraryAdd(array $req) {
  $uid  = (int)($req['user_id'] ?? 0);
  $work = trim($req['works_id'] ?? '');
  if ($uid <= 0 || $work === '') return ['status'=>'fail','message'=>'missing user_id or works_id'];

  $conn = db();
  $stmt = $conn->prepare("INSERT IGNORE INTO user_library (user_id, works_id) VALUES (?, ?)");
  if (!$stmt) return ['status'=>'fail','message'=>'prep failed'];
  $stmt->bind_param("is", $uid, $work);
  if (!$stmt->execute()) return ['status'=>'fail','message'=>'execute failed'];

  // INSERT IGNORE → if already there, affected_rows==0; still count as success
  return ['status'=>'success', 'message'=> ($stmt->affected_rows ? 'added' : 'already-in-library')];
}


// AIDA'S FUNCTIONS -- club features

// ---- feature 1: create club ----- 
function doCreateClub(array $req) {
  $owner_id = $req['user_id'] ?? 0;
  $name = $req['club_name'] ?? '';
  $desc = $req['description'] ?? '';

  if (!$owner_id || $name === '') {
    return ['status' => 'fail', 'message' => 'form is missing required fields'];
  }

  $conn = db();
  $stmt = $conn->prepare("INSERT INTO clubs (owner_id, name, description) VALUES (?, ?, ?)");
  $stmt->bind_param("iss", $owner_id, $name, $desc);
  if (!$stmt->execute()) {
    return ['status' => 'fail', 'message' => 'database insert failed: ' . $stmt->error];
  }

  return ['status' => 'success', 'club_id' => $conn->insert_id];
}


// ---- feature 2: invite member to club ----- 

function doInviteMember(array $req) {
  $club_id = $req['club_id'] ?? 0;
  $user_id = $req['user_id'] ?? 0;

  if (!$club_id || !$user_id) {
    return ['status' => 'fail', 'message' => 'missing parameters'];
  }

  $conn = db();
  // avoids duplicate club members hopefully. similar to our register logic
  $check = $conn->prepare("SELECT member_id FROM club_members WHERE club_id=? AND user_id=?");
  $check->bind_param("ii", $club_id, $user_id);
  $check->execute();
  $check->store_result();

  if ($check->num_rows > 0) {
    return ['status' => 'fail', 'message' => 'user is already a member'];
  }

  // inserts user into club db ""
  $stmt = $conn->prepare("INSERT INTO club_members (club_id, user_id) VALUES (?, ?)");
  $stmt->bind_param("ii", $club_id, $user_id);
  if (!$stmt->execute()) {
    return ['status' => 'fail', 'message' => $stmt->error];
  }

  return ['status' => 'success', 'message' => 'member invited'];
}


// ---- feature 3: create club event ----- 

function doCreateEvent(array $req) {
  $club_id = $req['club_id'] ?? 0;
  $title = $req['title'] ?? '';
  $date = $req['event_date'] ?? null;
  $desc = $req['description'] ?? '';

  if (!$club_id || $title === '') {
    return ['status' => 'fail', 'message' => 'form is missing required fields'];
  }

  $conn = db();
  $stmt = $conn->prepare("INSERT INTO club_events (club_id, title, event_date, description) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("isss", $club_id, $title, $date, $desc);
  if (!$stmt->execute()) {
    return ['status' => 'fail', 'message' => $stmt->error];
  }

  return ['status' => 'success', 'event_id' => $conn->insert_id];
}


// ---- feature 4: list club events ----- 

function doListEvents(array $req) {
  $club_id = $req['club_id'] ?? 0;
  if (!$club_id) return ['status' => 'fail', 'message' => 'missing club_id'];

  $conn = db();
  $stmt = $conn->prepare("SELECT event_id, title, event_date, description FROM club_events WHERE club_id=? ORDER BY event_date ASC");
  $stmt->bind_param("i", $club_id);
  $stmt->execute();
  $result = $stmt->get_result();

  $events = [];
  while ($row = $result->fetch_assoc()) {
    $events[] = $row;
  }

  return ['status' => 'success', 'events' => $events];
}


// ---- feature 5: cancel club event ----- 

function doCancelEvent(array $req) {
  $event_id = $req['event_id'] ?? 0;
  if (!$event_id) return ['status' => 'fail', 'message' => 'missing event_id'];

  $conn = db();
  $stmt = $conn->prepare("DELETE FROM club_events WHERE event_id=?");
  $stmt->bind_param("i", $event_id);
  if (!$stmt->execute()) {
    return ['status' => 'fail', 'message' => $stmt->error];
  }

  return ['status' => 'success', 'message' => 'event cancelled'];
}

// ---- feature 6: list clubs -----
function doList(array $req) {
  $user_id = $req['user_id'] ?? 0;
  if (!$user_id) return ['status' => 'fail', 'message' => 'missing user_id'];

  $conn = db();
  // user is owner or member of club
  $stmt = $conn->prepare("
    SELECT DISTINCT c.club_id, c.name, c.description, c.owner_id
    FROM clubs c
    LEFT JOIN club_members m ON c.club_id = m.club_id
    WHERE c.owner_id = ? OR m.user_id = ?
    ORDER BY c.name ASC
  ");
  $stmt->bind_param("ii", $user_id, $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  $clubs = [];
  while ($row = $result->fetch_assoc()) {
    $clubs[] = $row;
  }
  $stmt->close();
  $conn->close();

  return ['status' => 'success', 'clubs' => $clubs];
}


// --- REQUEST PROCESSOR ---

// decides which function to run
function requestProcessor($req) {
  echo "Received request:\n";
    var_dump($req);
    flush();
  
  if (!isset($req['type'])) {
    return ['status'=>'fail','message'=>'no type'];
  }

  switch ($req['type']) {
    case 'register': return doRegister($req);
    case 'login':    return doLogin($req);
    case 'validate': return doValidate($req);
    case 'logout':   return doLogout($req);
    case 'library.personal.remove': return doLibraryRemove($req);
    case 'library.review.list':   return doReviewsList($req);
    case 'library.review.create': return doReviewsCreate($req);
    case 'library.personal.list': return doLibraryList($req);
    case 'library.personal.add': return doLibraryAdd($req);
    case 'club.create': return doCreateClub($req);
    case 'club.invite': return doInviteMember($req);
    case 'club.list': return doList($req);
    case 'club.events.create': return doCreateEvent($req);
    case 'club.events.list': return doListEvents($req);
    case 'club.events.cancel': return doCancelEvent($req);
    default:         return ['status'=>'fail','message'=>'unknown type'];
  }
}

echo "Auth server ready, waiting for requests\n";
flush();

// multi-queue capable version of the queue

// uses pcntl_fork -->  https://www.php.net/manual/en/function.pcntl-fork.php
$which = $argv[1] ?? 'all';
$iniPath = __DIR__ . "/host.ini";

if ($which === 'all') { // to run all queues for DB and RMQ connection
    echo "Auth server starting for ALL queues...\n";
    $sections = ['AuthRegister', 'AuthLogin', 'AuthValidate', 
      'AuthLogout', 'LibrarySearch', 'LibraryDetails', 
      'LibraryCollect', 'LibraryPersonal', 'LibraryRemove', 
      'CreateReviews','ListReviews','LibraryAdd'];

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
    while (pcntl_wait($status) > 0) {}
} else {
    echo "Auth server starting for queue section: {$which}\n";
    $server = new rabbitMQServer($iniPath, $which);
    echo "Connecting to queue: {$which}\n";
    flush();
    $server->process_requests('requestProcessor');
    echo "Auth server stopped for {$which}\n";
}