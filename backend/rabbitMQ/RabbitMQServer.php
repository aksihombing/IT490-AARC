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
require_once __DIR__ . '/log_producer.php';
//require_once __DIR__ . '/library_process.php';

// connects to the local sql database
function db() {
  $host = 'localhost'; 
  $user = 'userAdmin'; 
  $pass = 'aarc490';
  $name = 'userdb'; 

  $mysqli = new mysqli($host, $user, $pass, $name);
  if ($mysqli->connect_errno) {
    throw new RuntimeException("DB connect failed: ".$mysqli->connect_error);
  }
  return $mysqli;
}



// request handlers

// --- FRONTEND PROCESSING ---
function doRegister(array $req) {
  $email = $req['email'] ?? '';
  $username = $req['username'] ?? '';
  $hash = $req['password'] ?? '';

// validate entered fields
  if ($email==='' || $username==='' || $hash==='') {
    return ['status'=>'fail','message'=>'missing fields'];
  }

  $hashedPassword = password_hash($password, PASSWORD_BCRYPT); // BCRYPT is an algorithm for hashing, supposedly more secure than SHA256

  $conn = db();

// see if user already exists in db
  $stmt = $conn->prepare("SELECT id FROM users WHERE username=? OR emailAddress=?");
  $stmt->bind_param("ss", $username, $email);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
    log_event("backend","warning","doRegister failed: user or email exists");
    return ['status'=>'fail','message'=>'user or email exists'];
  }
  $stmt->close();

// inserts new user into database
  $stmt = $conn->prepare("INSERT INTO users (username,emailAddress,password_hash) VALUES (?,?,?)");
  $stmt->bind_param("sss", $username, $email, $hash);
  if (!$stmt->execute()) {
    return ['status'=>'fail','message'=>'db insert failed'];
  }

  log_event("backend","info","New user registered: {$username}");
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
  $stmt->bind_param("s", $username);

  if (!$stmt->execute()){
    log_event("backend","warning","doLogin execute SELECT failed: " . $stmt->error);
    return ['status'=>'fail','message'=>'server error'];
  }
  
  $stmt->store_result();

  if ($stmt->num_rows === 1) { 
    // checks if theres a row in the db with from the query result
    $stmt->bind_result($uid,$dbUser,$dbHash);
    $stmt->fetch();
    $conn->query("DELETE FROM sessions WHERE user_id = $uid");
    
    if (password_verify($password,$dbHash)){
     // create a session key, should be secure ?
      $session = bin2hex(random_bytes(32));
      $exp = (new DateTime('+7 days'))->format('Y-m-d H:i:s');

      // stores the session in the db. change variable name in case it caused problems
      $stmt2 = $conn->prepare("INSERT INTO sessions (user_id, session_key, expires_at) VALUES (?, ?, ?)");
      if (!$stmt2) {
        error_log("doLogin preparing insert into sessions failed: " . $conn->error);
        log_event("backend","error","db login error: " . $stmt->error);
      } else {
        $stmt2->bind_param("iss", $uid, $session, $exp);
        if (!$stmt2->execute()) {
          error_log("doLogin execute insert into session failed: " . $stmt2->error);
          log_event("backend","error","db login error: " . $stmt->error);
        } else {
          error_log("doLogin Session inserted for uid={$uid}, session_key={$session}");
        }
      }
      log_event("backend","info","Login success for user {$dbUser}, session={$session}");

      return [
        'status' => 'success',
        'uid' => $uid,
        'username' => $dbUser,
        'session_key' => $session
      ];
    } else {
      log_event("backend","warning","Login failed, invalid password for {$username}");
      return ['status'=>'fail', 'message'=>'invalid password'];
    }
  } else {
    log_event("backend","warning","Login failed user not found: {$username}");
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
    log_event("backend","warning","Session expired for session_key={$sid}");
    $del = $conn->prepare("DELETE FROM sessions WHERE session_key=?");
    $del->bind_param("s",$sid);
    $del->execute();
    return ['status'=>'fail','message'=>'expired'];
  }
  log_event("backend","info","Session validated for user: {$uname}");
  return ['status'=>'success','user'=>['id'=>$uid,'username'=>$uname,'email'=>$email]];
}

function doLogout(array $req) {
  $sid = $req['session_key'] ?? '';
  log_event("backend","info","Logout request for session {$sid}");

  if ($sid==='') return ['status'=>'fail','message'=>'missing session'];

  $conn = db();
  $stmt = $conn->prepare("DELETE FROM sessions WHERE session_key=?");
  $stmt->bind_param("s",$sid);
  $stmt->execute();
  return ['status'=>'success'];
}

// --- MIDTERM GROUP DELIVERABLES (WEBSITE FEATURES) ---

// CHIZZY'S FUNCTIONS

// REVIEWS -----------------
//gets all the reviews for a specific book
function doReviewsList(array $req)
{
  $works_id = trim($req['works_id'] ?? '');
  log_event("backend","info","Review list request for works_id={$works_id}");

  if ($works_id === '')
    return ['status' => 'fail', 'message' => 'missing works_id'];



  $conn = db();

  $stmt = $conn->prepare("
    SELECT r.user_id, u.username, r.rating, r.body, r.created_at
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.works_id = ?
    ORDER BY r.created_at DESC
    LIMIT 20
  ");
  $stmt->bind_param("s", $works_id);
  $stmt->execute();
  $reviewResults = $stmt->get_result();

  $allReviews = [];
  while ($oneReview = $reviewResults->fetch_assoc()){// gets each row returned from query; only returns what is needed
    $allReviews[] = [
      'username' => $oneReview["username"],
      'rating' => (int)$oneReview["rating"],
      'body' => $oneReview["body"],
      'created_at' => $oneReview['created_at']
    ];
  }

  return [
    'status' => 'success',
    'items' => $allReviews
  ];
}

function doReviewsCreate(array $req)
{
  $user_id = (int) ($req['user_id'] ?? 0);
  $works_id = trim($req['works_id'] ?? '');
  $rating = (int) ($req['rating'] ?? 0);
  $body = trim($req['body'] ?? ($req['comment'] ?? ''));

  log_event("backend","info","Create review request: user={$user_id}, works_id={$works_id}, rating={$rating}");

  if ($user_id <= 0 || $works_id === '' || $rating < 1 || $rating > 5) {
    return ['status' => 'fail', 'message' => 'missing or invalid fields'];
  }

  // DEBUGGING
  /*
  echo "Received doReviewsCreate -----". "\n";
  echo "user_id: " . $user_id . "\n";
  echo "works_id: " . $works_id. "\n";
  echo "rating: " . $rating. "\n";
  echo "body: " . $body. "\n";
*/
  $conn = db();

  $stmt = $conn->prepare("
    INSERT INTO reviews (user_id, works_id, rating, body)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE rating=VALUES(rating), body=VALUES(body), created_at=NOW()
  ");
  $stmt->bind_param("isis", $user_id, $works_id, $rating, $body);
  $ok = $stmt->execute();

  if (!$ok) {
    return
      [
        'status' => 'fail',
        'message' => 'database error: ' . $stmt->error
      ];
  } else {
    return ['status' => 'success', 'message' => 'review saved'];
  }
}

// LIBRARY --------------

function doLibraryList(array $req) {
  $user_id = (int)($req['user_id'] ?? 0);

  log_event("backend","info","Library list request for user_id={$user_id}");

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
    LIMIT 10
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

  log_event("backend","info","Library add request: user={$uid}, work={$work}");

  if ($uid <= 0 || $work === '') return ['status'=>'fail','message'=>'missing user_id or works_id'];

  $conn = db();
  $stmt = $conn->prepare("INSERT IGNORE INTO user_library (user_id, works_id) VALUES (?, ?)");
  if (!$stmt) return ['status'=>'fail','message'=>'prep failed'];
  $stmt->bind_param("is", $uid, $work);
  if (!$stmt->execute()) return ['status'=>'fail','message'=>'execute failed'];

  // INSERT IGNORE → if already there, affected_rows==0; still count as success
  return ['status'=>'success', 'message'=> ($stmt->affected_rows ? 'added' : 'already-in-library')];
}


//removes a book from user's library
function doLibraryRemove(array $req)
{
  $uid = (int) ($req['user_id'] ?? 0);
  $work = $req['works_id'] ?? '';

  log_event("backend","info","Library remove request: user={$uid}, work={$work}");


  if (!$uid || $work === '')
    return ['status' => 'fail', 'message' => 'missing user_id or works_id'];

  $conn = db();
  $stmt = $conn->prepare("DELETE FROM user_library WHERE user_id=? AND works_id=? LIMIT 1");
  if (!$stmt)
    return ['status' => 'fail', 'message' => 'prep failed'];
  $stmt->bind_param("is", $uid, $work);
  if (!$stmt->execute())
    return ['status' => 'fail', 'message' => 'execute failed'];

  return ($stmt->affected_rows > 0)
    ? ['status' => 'success']
    : ['status' => 'fail', 'message' => 'not found'];
}


// AIDA'S FUNCTIONS -- club features

// ---- feature 1: create club ----- 
function doCreateClub(array $req) {
  $owner_id = $req['user_id'] ?? 0;
  $name = $req['club_name'] ?? '';
  $desc = $req['description'] ?? '';

  log_event("backend","info","Create club request: owner={$owner_id}, name={$name}");

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

  log_event("backend","info","Invite club member request: club={$club_id}, user={$user_id}");

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
  $creatorUserID = $req['user_id'] ?? 0;
  $club_id = $req['club_id'] ?? 0;
  $title = $req['title'] ?? '';
  $date = $req['event_date'] ?? null;
  $desc = $req['description'] ?? '';

  log_event("backend","info","Create event request: club={$club_id}, title={$title}");

  if (!$club_id || $title === '') {
    return ['status' => 'fail', 'message' => 'form is missing required fields'];
  }

  $conn = db();
  $stmt = $conn->prepare("INSERT INTO events (creatorUserID, club_id, title, event_date, description) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("iisss", $creatorUserID, $club_id, $title, $date, $desc);
  if (!$stmt->execute()) {
    return ['status' => 'fail', 'message' => $stmt->error];
  }

  return ['status' => 'success', 'event_id' => $conn->insert_id];
}


// ---- feature 4: list club events ----- 

function doListEvents(array $req) {
  $club_id = $req['club_id'] ?? 0;
  if (!$club_id) return ['status' => 'fail', 'message' => 'missing club_id'];

  log_event("backend","info","List events request for club={$club_id}");

  $conn = db();
  $stmt = $conn->prepare("SELECT event_id, title, event_date, description FROM events WHERE club_id=? ORDER BY event_date ASC");
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

  log_event("backend","info","Cancel event request for event={$event_id}");

  $conn = db();
  $stmt = $conn->prepare("DELETE FROM events WHERE event_id=?");
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

  log_event("backend","info","List clubs request ffrom user={$user_id}");

  $conn = db();
  // user is owner or member of club
  $stmt = $conn->prepare("
    SELECT DISTINCT c.club_id, c.name, c.description, c.owner_id
    FROM clubs c
    LEFT JOIN club_members m ON c.club_id = m.club_id
    WHERE c.owner_id = ? OR m.user_id = ?
    ORDER BY c.name ASC");
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

// ---- feature 7: generate  invite link -----
function doInviteLink(array $req) {
  $club_id = $req['club_id'] ??0;
  if (!$club_id) return ['status'=> 'fail', 'message' => 'missing user_id'];

  log_event("backend","info","Generate invite link request for club={$club_id}");

  $conn = db();
  $hash = bin2hex(random_bytes(16));
  
  $stmt = $conn->prepare("INSERT INTO club_invites(club_id,hash) VALUES (?,?)");
  $stmt->bind_param("is", $club_id, $hash);
  if (!$stmt->execute()) {
    return ['status' => 'fail', 'message' => 'cant generate link:'.$stmt->error];
  }

  $link = "http://www.aarc.com/inviteJoin.php?invite=$hash"; //only works for my test web vm need to change for chizi's file path
  return ['status'=>'success','link'=>$link];
}

// ---- feature 8: invite link join -----
function doInviteJoin(array $req) {$hash = $req['hash'] ?? '';
  $user_id = $req['user_id'] ?? 0;
  $hash = $req['hash'] ?? '';
  if ($hash === '' || !$user_id) return ['status'=>'fail','message'=>'missing data'];

  log_event("backend","info","Invite join request for user={$user_id}");
    
  $conn = db();
  $stmt = $conn->prepare("SELECT club_id FROM club_invites WHERE hash=? LIMIT 1");
  $stmt->bind_param("s", $hash);
  $stmt->execute();
  $stmt->bind_result($club_id);
  if (!$stmt->fetch()) return ['status'=>'fail','message'=>'invalid or expired link'];
  $stmt->close();

  $join = $conn->prepare("INSERT INTO club_members(club_id,user_id) VALUES (?,?)");
  $join->bind_param("ii", $club_id, $user_id);
  if (!$join->execute()) return ['status'=>'fail','message'=>$join->error];

  return ['status'=>'success','message'=>'joined club successfully'];
}


// --- LIBRARY API

function apidb()
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


// ---- CACHE DATABASE ----

// bookCache(array $data)
function bookCache_check_query(array $req) // check cache book ONE AT A TIME
{
    try {
        $type = $req['searchType'] ?? 'title';
        $query = strtolower(trim($req['query'] ?? ''));

        if ($query === '')
            return ['status' => 'fail', 'message' => 'missing query'];

        $limit = isset($req['limit']) && is_numeric($req['limit']) ? (int) $req['limit'] : 10;
        $page = isset($req['page']) ? intval($req['page']) : 1;

        // CACHE CHECK ----------------------------------
        $mysqli = apidb();

        echo "Checking cache for: type={$type}, query='{$query}', limit={$limit}, page={$page}\n";
        log_event("backend","info","Cache check: type={$type}, query={$query}, page={$page}");

        // check for search_type, query, page_num AND check if expired.
        $check_cache = $mysqli->prepare("SELECT * FROM library_cache WHERE search_type=? AND query=? AND page_num=? AND expires_at > NOW() LIMIT ?");
        $check_cache->bind_param("ssii", $type, $query, $page, $limit);
        $check_cache->execute();
        $cache_result = $check_cache->get_result();

        if ($cache_result->num_rows > 0) {
            echo "Cache HIT for {$type}={$query}\n";
            log_event("backend","info","Cache HIT for {$type}={$query}");
            $cachedData = [];

            while ($row = $cache_result->fetch_assoc()) {
                $cachedData[] = $row;
            }
            $mysqli->close();
            return [
                'status' => 'success',
                'data' => $cachedData
            ];
            // return cache HIT
        } else {
            log_event("backend","info","Cache MISS for {$type}={$query}");
            $mysqli->close();
            return [
                'status' => 'fail' // could be expired OR not in cache
            ];
        }
    } catch (Exception $e) {
        log_event("backend","error","Cache check error: " . $e->getMessage());
        return [
            'status' => 'fail',
            'message' => "Error processing request: " . $e->getMessage()
        ];
    }
}





// book search by OLID
function bookCache_check_olid(string $olid) // check cache book ONE AT A TIME
{
    try {
        $mysqli = apidb();

        echo "Checking cache for: olid = {$olid}\n";
        log_event("backend","info","Cache check OLID={$olid}");


        // check for olid, does NOT check if it is expired, but it should be fine
        $check_cache = $mysqli->prepare("SELECT * FROM library_cache WHERE olid=?");
        $check_cache->bind_param("s", $olid);
        $check_cache->execute();
        $cache_result = $check_cache->get_result();

        if ($cache_result->num_rows > 0) {
            echo "Cache HIT for OLID {$olid}\n";
            log_event("backend","info","Cache HIT for OLID={$olid}");
            $cachedData = $cache_result->fetch_assoc();

            $mysqli->close();
            return [
                'status' => 'success',
                'data' => $cachedData
            ];
            // return cache HIT
        } else {
            $mysqli->close();
            echo "Did not find {$olid} in cache\n";
            log_event("backend","info","Cache MISS for OLID={$olid}");
            return [
                'status' => 'fail' // could be expired OR not in cache
            ];
        }
    } catch (Exception $e) {
        log_event("backend","error","Cache OLID error: " . $e->getMessage());
        return [
            'status' => 'fail',
            'message' => "Error processing request: " . $e->getMessage()
        ];
    }
}




// add to cache
function bookCache_add(array $req) // add book ONE AT A TIME
{
    $mysqli = apidb();

    log_event("backend","info","Adding to cache: OLD={$olid}, title={$title}");
    $insertToTable = $mysqli->prepare("
    INSERT INTO library_cache (
      search_type, query, page_num, olid, title, author, isbn,
      book_desc, publish_year, ratings_average, ratings_count,
      subjects, person_key, place_key, time_key, cover_url
    )
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
      title=VALUES(title),
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
  ");
    try {
        // read array

        $type = $req['searchType'] ?? 'title';
        $query = strtolower(trim($req['query'] ?? ''));
        $page = isset($req['page']) ? intval($req['page']) : 1; // default to 1 if no page specified

        $olid = $req['olid'] ?? [];
        $title = $req['title'] ?? 'Unknown title';
        $author = $req['author'] ?? 'Unknown author';
        $isbn = $req['isbn'] ?? []; // returns ALL isbns for ALL editions but honestly doesn't matter if its not THAT accurate for now
        $book_desc = $req['book_desc'] ?? 'No book description available'; // not returned from search endpoint
        $publish_year = $req['publish_year'] ?? [];
        $ratings_average = $req['ratings_average'] ?? [];
        $ratings_count = $req['ratings_count'] ?? [];
        $subjects = $req['subjects'] ?? [];
        $person_key = $req['person_key'] ?? [];
        $place_key = $req['place_key'] ?? [];
        $time_key = $req['time_key'] ?? [];
        $cover_url = $req['cover_url'] ?? [];

        // cache save
        echo "Saving to cache: type={$type}, query='{$query}'\n"; // debugging


        $insertToTable->bind_param(
            "ssisssssidisssss",
            $type, // string
            $query, // string
            $page, // int
            $olid, // string
            $title, // string
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
        );

        $insertToTable->execute();
        $mysqli->close();
        return ['status' => 'success']; // to verify completion
    } catch (Exception $e) {
        log_event("backend","error","Cache add error: " . $e->getMessage());
        $mysqli->close();
        return [
            'status' => 'fail',
            'message' => "Error processing request: " . $e->getMessage()
        ];
    }
}






// Cache Tables Pre-Populated via cron
function getRecentBooks()
{
    try {
        $mysqli = apidb();
        $result = $mysqli->query("SELECT * FROM recentBooks ORDER BY publish_year DESC ");

        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        $mysqli->close();
        return ['status' => 'success', 'data' => $books];

    } catch (Exception $e) {
        error_log("getRecentBooks() error: " . $e->getMessage());
        $mysqli->close();
        return [
            "status" => "error",
            "message" => "Failed to load recent books: " . $e->getMessage()
        ];
    }
}


// to send requests to API/DMZ to update itself

function doBookCollect(array $req)
{
    $requestType = $req['type'] ?? null;
    log_event("backend","info","doBookCollect request type={$requestType}");

    // check internal cache for details --> depends on if the request type was book_search or book_details
    $error = null; // backup error checking since so many things can go wrong
    $responseData = null;

    switch ($requestType) {
        case 'book_search':
            $cache_check = bookCache_check_query($req);
            if ($cache_check['status'] === 'success') {
                $responseData = $cache_check['data'];
            } else {
                echo "Cache MISSED for book_search -- calling DMZ\n";
                log_event("backend","info","Cache MISS for book_search -- calling DMZ");

                $req['type'] = 'api_book_search';
                // had to update type so that the library listener LISTENS for this request type

                $client = new rabbitMQClient(__DIR__ . "/../rabbitMQ/host.ini", "LibrarySearch");

                // update request 'type' to api_book_search or api_book_details
                $DMZresponse = $client->send_request($req); // response from dmz
                //var_dump($response); //debugging 

                if ($DMZresponse['status'] === 'success' && !empty($DMZresponse['data'])) {
                    $responseData = $DMZresponse['data'];
                    foreach ($responseData as $book) {
                        bookCache_add($book); // update internal apidb with ALL books that are returned
                    }
                } else {
                    log_event("backend","error","DMZ search error: " . $error);
                    $error = $DMZresponse['message'] ?? 'Unknown error from server.';
                }
            }
            break;



        case 'book_details':
            $cache_check = bookCache_check_olid($req['olid']);
            if ($cache_check['status'] === 'success') {
                $responseData = $cache_check['data'];
            } else {
                echo "Cache MISSED for book_details -- calling DMZ\n";
                log_event("backend","info","Cache MISS for book_details -- calling DMZ");
                $req['type'] = 'api_book_details';
                // had to update type so that the library listener LISTENS for this request type

                $client = new rabbitMQClient(__DIR__ . "/../rabbitMQ/host.ini", "LibraryDetails");

                // update request 'type' to api_book_search or api_book_details
                $DMZresponse = $client->send_request($req); // response from dmz
                //var_dump($response); //debugging 

                if ($DMZresponse['status'] === 'success' && !empty($DMZresponse['data'])) {
                    $responseData = $DMZresponse['data'];
                    bookCache_add($responseData); // only one book detail is returned at a time
                }
                else {
                    log_event("backend","error","DMZ details error: " . $error);
                    $error = $DMZresponse['message'] ?? 'Unknown error from server.';
                }
            }
            break;

        default:
            return [
                "status" => "error",
                "message" => "Request error in library_process"
            ];
    } // end switch

    if ($responseData) {
        return [
            'status' => 'success',
            'data' => $responseData
        ];
    } else {
        return [
            "status" => "error",
            "message" => $error ?? "No results from library API found"
        ];
    }

}






// --- REQUEST PROCESSOR ---

// decides which function to run
function requestProcessor($req) {
  echo "----------------------\n";
  echo "Received request:\n";

  log_event("backend","info","Received request: type={$req['type']}");
  
  var_dump($req);
  flush();
  
  if (!isset($req['type'])) {
    log_event("backend","error","Unknown request type: {$req['type']}");
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
    case 'club.invite_link': return doInviteLink($req);
    case 'club.join_link' : return doInviteJoin($req);
    // for book api stuff
    case 'recent_books' : return getRecentBooks();
    case 'book_search' : return doBookCollect($req);
    case 'book_details' : return doBookCollect($req);
    default: return ['status'=>'fail','message'=>'unknown type'];
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
      'AuthLogout', 'LibraryPersonal', 'LibraryRemove', 
      'CreateReviews','ListReviews','LibraryAdd','ClubProcessor', 'LibrarySearch', 'LibraryDetails'];
      // LibraryCollect is for the specific connection between database > rmq > api

    foreach ($sections as $section) {
        $pid = pcntl_fork(); // process control fork; creats child process 
        if ($pid == -1) {
            log_event("backend","error","Failed to fork proccess for {$section}");
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
?>
