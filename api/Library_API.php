#!/usr/bin/php
<?php
// rmq script to stay running and listen for messages. listener
// when it receives a message, it should check the local apidb, request from the API itself if the query is not populated yet in the db, and then send back to the frontend

// uses cURL for GET, POST, etc.. requests to/from API.
// https://stackoverflow.com/questions/9802788/call-a-rest-api-in-php
// https://weichie.com/blog/curl-api-calls-with-php/

// HUGE WORK IN PROGRESS !!!

// NEED TO UPDATE:
// doBookSearch only actually needs to cache and return OLID, Title, Author, Publish_Year, cover_url, and isbn because we don't really DISPLAY the other information. We can run it through doBookDetails, now that I'm thinking about it.
// I also realized it probably wouldve been better to make a singular function to select data PER endpoint
/* 
/works/olid.json    
/works/olid/editions.json   
/search.json   
/works/olid/rations.json    
/subjects.json
*/

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


  // FEATURE PROPOSAL : page, offset, and limit parameters
  $limit = isset($req['limit']) && is_numeric($req['limit']) ? $req['limit'] : 10; // default limit is 10
  $page = isset($req['page']) ? intval($req['page']) : 1; // default page starts at 1
  $offset = ($page - 1) * $limit; // (page number - 1) * number of results per page



  // CACHE CHECK ----------------------------------
  $mysqli = db();

  echo "Checking cache for: type={$type}, query='{$query}', limit={$limit}, page={$page}\n"; //debugging

  $check_cache = $mysqli->prepare("SELECT * FROM library_cache WHERE search_type=? AND query=? AND pageNum=? AND expires_at > NOW() LIMIT ?"); // might need to change limit ? idk
  $check_cache->bind_param("ssii", $type, $query, $page, $limit);
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
  $searchurl = "{$base}?q={$encodedQuery}&limit={$limit}&page={$page}";


  $search_response = curl_get($searchurl);
  $curl_data = json_decode($search_response, true); // true is for the associative arrays. if false, it returns the json objects into objects. make sure to decode the response from the api before upserting/ inserting it back into the db

  if (empty($curl_data['docs']))
    return ['status' => 'fail', 'message' => 'no results']; // no results found

  // prepare insertToTable before reading the docs that are returned because reading each result from the query will be done in a FOREACH loop !!
  // $insertToTable will be repeatedly called from the loop
  $insertToTable = $mysqli->prepare("
    INSERT INTO library_cache (
      search_type, query, pageNum, olid, title, subtitle, author, isbn,
      book_desc, publish_year, ratings_average, ratings_count,
      subjects, person_key, place_key, time_key, cover_url
    )
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
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
  ");
  // ** need to look into if last_updated is updated correctly ?

  // https://github.com/internetarchive/openlibrary/blob/b4afa14b0981ae1785c26c71908af99b879fa975/openlibrary/plugins/worksearch/schemes/works.py#L119-L153

  $searchbookresults = []; // empty array to get sent to the webserver, idk wehre it should actually go
  // needs to be declared outside the loop to collect ALL book data

  foreach ($curl_data['docs'] as $book) { // FOREACH BOOK START
    // reading each doc that was returned
    $olid = str_replace('/works/', '', $book['key'] ?? null); //string --> cover_edition_key was specific to the edition of a book and not the actual OLID value in /works/
    $title = $book['title'] ?? 'Unknown title'; //string
    $subtitle = $book['subtitle'] ?? null; //string
    $author = $book['author_name'][0] ?? 'Unknown author'; //string
    $publish_year = $book['first_publish_year'];
    $cover_url = !empty($book['cover_i'])
      ? "https://covers.openlibrary.org/b/id/" . $book['cover_i'] . "-L.jpg" : null; // ternary -> if cover_i is set, then it saves the link
    // cover_i only saves the id of where the cover is, so we have to build the link manually
    // gets the -L (Large) version of the image


    // data from /works/{OLID}.json ----------------------

    $work_url = "https://openlibrary.org/works/{$olid}.json";
    $work_json = curl_get($work_url);

    $book_desc = 'No book description available';
    $subjects = null;
    $person_key = null;
    $place_key = null;
    $time_key = null;

    if ($work_json) {
      $work_data = json_decode($work_json, true); // decode to read all data


      if (is_array($work_data['description'])) {
        $book_desc = $work_data['description']['value'];
      } // some books have an array for description
      else if (is_string($work_data['description'])) {
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


    // data from /works/{OLID}/editions.json ----------------------

    $editions_url = "https://openlibrary.org/works/{$olid}/editions.json?limit=1"; // only get 1 of the editions isbn
    $editions_json = curl_get($editions_url);

    if ($editions_json) {
      $editions_data = json_decode($editions_json, true);
      $first_entry = $editions_data['entries'][0]; // gets the first entry result bc it will have ALL editions with different isbns listed. this is good enough for now, but it might be good to have a list of isbns.

      if (!empty($first_entry['isbn_13'][0])) {
        $isbn = $first_entry['isbn_13'][0];
      } elseif (!empty($first_entry['isbn_10'][0])) {
        $isbn = $first_entry['isbn_10'][0];
      } else {
        $isbn = null; // no isbn found
      }
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


    // INSERT INTO TABLE ON CACHE MISS ! ----------------------------------

    echo "Saving to cache: type={$type}, query='{$query}'\n"; // debugging

    // binding params for such a big table... nightmare fuel for anyone who craves efficiency

    $insertToTable->bind_param(
      "ssissssssidisssss",
      $type, // string
      $query, // string
      $page, // int
      $olid, // string
      $title, // string
      $subtitle, // string
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
    ); // need to check if this was done correctly bc its just TOOO much

    $insertToTable->execute();

    $searchbookresults[] = [ // this gets returns to the webserver
      'page' => $page,
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


    echo "Cache MISS (fetched + saved) for {$type} = {$query}\n";
  } // END FOREACH BOOK

  return [
    'status' => 'success',
    'data' => $searchbookresults,
    'limit' => $limit,
    'page' => $page,
    'offset' => $offset, // calculates and sends offset back to frontend; not sure how it fully works tho
  ];
}


// Cache Tables Pre-Populated via cron
function getRecentBooks()
{
  try {
    $mysqli = db();
    $result = $mysqli->query("SELECT * FROM recentBooks ORDER BY publish_year DESC "); // switched to return all fields from recentBooks

    // $result = $mysqli->query("SELECT title, author, publish_year, cover_url FROM recentBooks ORDER BY publish_year DESC "); // LIMIT 10 will return 10 results but the database only has 10 entries anyway

    $books = [];
    while ($row = $result->fetch_assoc()) {
      $books[] = $row;
    }
    return ['status' => 'success', 'data' => $books];

  } catch (Exception $e) {
    error_log("getRecentBooks() error: " . $e->getMessage());
    return [
      "status" => "error",
      "message" => "Failed to load recent books: " . $e->getMessage()
    ];
  }
}

// (( scrapped Popular_books ))



// Get Book Details (On-The-Spot GET request to API, no caching in apidb here)
function doBookDetails(array $req)
{
  // reuses doBookSearch format
  $olid = $req['olid'] ?? $req['works_id'] ?? ''; // check for olid or works_id
  if ($olid === '')
    return ['status' => 'fail', 'message' => 'missing olid for query'];

  $encodedOlid = urlencode($olid);
  // this is based on if OLID actually exists
  $work_url = "https://openlibrary.org/works/{$encodedOlid}.json";
  $work_json = curl_get($work_url);
  $work_data = json_decode($work_json, true);


  if (!$work_data)
    return ['status' => 'fail', 'message' => 'Failed to get /works/ information'];


  // data from /works/{OLID}.json ----------------------

  $title = $work_data['title'] ?? 'Unknown title';

  if (isset($work_data['description'])) { // check if it actually exists
    if (is_array($work_data['description'])) {
      $book_desc = $work_data['description']['value'];
    } // some books have an array for description
    else if (is_string($work_data['description'])) {
      $book_desc = $work_data['description'];
    } else {
      $book_desc = "No book description available";
    }
  } else {
    $book_desc = "No book description available";
  }
  // need to encode the json because the database column is of JSON type



  $subjects = json_encode(array_slice($work_data['subjects'] ?? [], 0, 20)); // take the first 20 subjects max
  $person_key = json_encode(array_slice($work_data['subject_people'] ?? [], 0, 20));
  $place_key = json_encode(array_slice($work_data['subject_places'] ?? [], 0, 20));
  $time_key = json_encode(array_slice($work_data['subject_times'] ?? [], 0, 20));

  // for search.json!!!  ----------------------
  $searchbase = "https://openlibrary.org/search.json"; //base url for endpoint
  $encodedQuery = urlencode($title); // url encodes query when its actually getting sent to the API
  $searchurl = "{$searchbase}?q={$encodedQuery}&limit=1";

  $search_response = curl_get($searchurl);
  $curl_data = json_decode($search_response, true);


  $author = 'Unknown author';
  $subtitle = null;
  $publish_year = null;
  $cover_url = null;

  if ($curl_data && isset($curl_data['docs'][0])) { // get first doc only
    $doc = $curl_data['docs'][0]; // we only care about the first result
    $subtitle = $doc['subtitle'] ?? null; //string
    $author = $doc['author_name'][0] ?? 'Unknown author'; //string
    $publish_year = $doc['first_publish_year'];
    $cover_url = !empty($doc['cover_i'])
      ? "https://covers.openlibrary.org/b/id/" . $doc['cover_i'] . "-L.jpg" : null; // ternary -> if cover_i is set, then it saves the link
    // cover_i only saves the id of where the cover is, so we have to build the link manually
    // gets the -L (Large) version of the image

  }

  // data from /works/{OLID}/editions.json ----------------------
  $isbn = null;

  $editions_url = "https://openlibrary.org/works/{$olid}/editions.json?limit=1"; // only get 1 of the editions isbn
  $editions_json = curl_get($editions_url);

  if ($editions_json) {
    $editions_data = json_decode($editions_json, true);
    $first_entry = $editions_data['entries'][0]; // gets the first entry result bc it will have ALL editions with different isbns listed. this is good enough for now, but it might be good to have a list of isbns.

    if (!empty($first_entry['isbn_13'][0])) {
      $isbn = $first_entry['isbn_13'][0];
    } elseif (!empty($first_entry['isbn_10'][0])) {
      $isbn = $first_entry['isbn_10'][0];
    } else {
      $isbn = null; // no isbn found
    }

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


  // returning results
  $bookDetailsResults = [ // this gets returns to the webserver
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
    'person_key' => $person_key,
    'place_key' => $place_key,
    'time_key' => $time_key,
    'cover_url' => $cover_url
  ];


  echo "Returning details for {$olid}={$title}\n";
  return [
    'status' => 'success',
    'data' => $bookDetailsResults
  ];



} // end doBookDetails




function doBookRecommend(array $req)
{  // 1 to 1 book recommendation for the sake of speed
    // content-based filtering --> uses subjects to recommend a book
    // https://openlibrary.org/dev/docs/api/subjects

    // reuse doBookDetails/doBookSearch

    // read olid of one book
    $olid = $req['olid'] ?? $req['works_id'] ?? ''; // check for olid or works_id
    if ($olid === '')
        return ['status' => 'fail', 'message' => 'missing olid for query'];

    // find subjects[]
    // data from /works/{OLID}.json ----------------------

    $work_url = "https://openlibrary.org/works/{$olid}.json";
    $work_json = curl_get($work_url);
    if (!$work_json) { // fail catching jusssstt in case
        return ['status' => 'fail', 'message' => 'failed to fetch work'];
    }
    $work_data = json_decode($work_json, true); // decode to read all data


    // $allSubjects = array_map('strtolower', array_slice($work_data['subject'] ?? [], 0, 20));// get all subjects returned, limit to first 20 subjects and makes sure its all lowercase
    $allSubjects_raw = array_map('strtolower', array_slice($work_data['subjects'] ?? [], 0, 50)); // grab nearly all subjects
    // NOTE : /works/ uses PLURAL 'subjects' ....
    $filteredSubjects = []; // filtered subjects, 1-word

    foreach ($allSubjects_raw as $filtering_subject) {
        $filtering_subject = strtolower(trim($filtering_subject)); // lowercase, trimmed

        // https://www.php.net/manual/en/function.preg-match.php
        // https://regexr.com/
        // regex for php </3
        if (!preg_match('/^[a-z\-]+$/', $filtering_subject))
            continue; // one-word subjects, allowing for hyphenated subjects also
        // exclude anything with accented characters (beyond ascii char code 122)

        $filteredSubjects[] = $filtering_subject; // add good, single word subject to array
    }


    $allSubjects = array_slice($filteredSubjects, 0, 20); // not sure if slicing is needed here
    shuffle($allSubjects); // easier to select a random selection of two subjects to search with
    //print_r($allSubjects); // DEBUGGING


    // randomly select 2 subjects within the first 20 subjects
    $subject1 = $allSubjects[0]; // will get entered into subjects/{subject1}.json, PRIMARY SEARCH

    //echo "Primary subject: {$subject1}\n"; // DEBUGGING

    $subject2 = array_slice($allSubjects, 1, 20); //skip first array item (which is used as the primary search), use next 20 subjects as a fallback in case there isnt a match with any one of them
    //echo "Second subject options: \n"; // DEBUGGING
    //var_dump($subject2);

    // subjects/{subject}.json search query
    // search results for another book in "works" that has a "subject" item equal to $random_subjects[1] --> Only recommend first match
    $encodedSubject1 = urlencode($subject1);
    $subjectUrl = "https://openlibrary.org/subjects/{$encodedSubject1}.json?sort=new&sort=rating%20desc&limit=50";
    $subject_json = curl_get($subjectUrl);
    $subject_data = json_decode($subject_json, true);
    $works = $subject_data["works"] ?? [];

    $recommendedBook = null;

    // regex and trim filtering for subjects for the recommended books



    // return recommended book's olid --> maybe return 
    foreach ($works as $oneBook) {

        $rec_work_id = $oneBook['key'] ?? ''; // key: XXX is there the works_id is
        if (!$rec_work_id)
            continue; // if work id not found, keep going

        // formatted like "key" : "/works/OLxxxxxW", we only want the OLxxxxxW
        $rec_olid = str_replace('/works/', '', $rec_work_id);
        if ($rec_olid === $olid)
            continue; // skip if its the same book

        // require 2nd subject match also

        // fallback : strtolower subjects to make sure matching fails arent due to case sensitivity
        // https://www.php.net/manual/en/function.array-map.php --> used array mapping bc subject is an array
        $rec_subjects_raw = array_map('strtolower', $oneBook['subject'] ?? []);
        $rec_subjects = [];
        foreach ($rec_subjects_raw as $r_subject) {
            $r_subject = trim($r_subject);

            // regex for php </3 -- same as previous filtering
            if (!preg_match('/^[a-z\-]+$/', $r_subject))
                continue;

            $rec_subjects[] = $r_subject; // add good, single word subject to array
        }

        //echo "filtered rec_subjects:";
        //print_r($rec_subjects); // DEBUGGING
        //https://www.php.net/manual/en/function.array-intersect.php


        $matchedSubject = array_intersect($rec_subjects, $subject2);
        //echo "found matched subject: "; // DEBUGGING
        //print_r(array_values($matchedSubject)); // DEBUGGING

        if (empty($matchedSubject))
            continue; // goes to next iteration until match found

        // should only reach this area if a return is found
        $recommendedBook = [
            'olid' => $rec_olid,
            'title' => $oneBook['title'] ?? 'Unknown',
            'author' => $oneBook['authors'][0]['name'] ?? 'Unknown',
            'publish_year' => $oneBook['first_publish_year'] ?? null,
            'cover_url' => isset($oneBook['cover_id']) // note: stored as cover_id and not cover_i via subjects endpoint
                ? "https://covers.openlibrary.org/b/id/" . $oneBook['cover_id'] . "-L.jpg"
                : null,
            //'matched_subjects' => [$subject1, $subject2] // not really needed to be returned
        ];
        break;
    }

    if (!$recommendedBook) {
        return ['status' => 'fail', 'message' => 'no recommendation found'];
    }

    echo "Found match: " . $olid . " || Matched with " . $rec_olid . "with subjects: " . implode(', ', $matchedSubject) . "\n";

    return [
        'status' => 'success',
        'recommended_book' => $recommendedBook
    ];


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
