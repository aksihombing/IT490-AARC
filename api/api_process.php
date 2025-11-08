<?php
require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/get_host_info.inc';
require_once __DIR__ . '/api_endpoints.php';
// curl_get() + simple_sanitize() + api_search()
require_once __DIR__ . '/api_cache.php';
// db() + bookCache_check_query() + bookCache_check_olid() + bookCache_add()
// decides which function to run


// PROCESS API DATA -----------------------
// use search.json
function doBookSearch(array $req)
{
  $cache_check = bookCache_check_query($req);
  if ($cache_check['status'] === 'success') {
    return [
      'status' => 'success',
      'data' => $cache_check['data']
    ];
  }

  // cache MISS
  $search_api = api_search($req);

  if ($search_api['status'] === 'fail') {
    return [
      "status" => "fail",
      "message" => "No results found"
    ];
  }

  $books = $search_api['data'];
  // $addedCount = 0; // could count how many books were added for debugging if needed

  foreach ($books as $book) {
    try {
      bookCache_add($book);
    } catch (Exception $e) {
      error_log("Failed to cache book with OLID= {$book['olid']} || Error: " . $e->getMessage());
    }
  }

  return [
    'status' => 'success',
    'data' => $books,
    'limit' => $search_api['limit'],
    'page' => $search_api['page'],
  ];
}

// doBookDetails () 
// any book that is viewed should TECHNICALLY already be loaded into the cache so i dont think i need to manually get any book that ISNT in the cache already ?
function doBookDetails(array $req) //only gets ONE BOOK'S DETAILS
{
  $olid = $req['olid'] ?? $req['works_id'] ?? null;
  if (!$olid) {
    return [
      'status' => 'fail',
      'message' => 'Missing OLID parameter for bookDetails'
    ];
  }

  $cache_check = bookCache_check_olid($olid);
  if ($cache_check['status'] === 'success') { // if book is found in the cache
    return [
      'status' => 'success',
      'data' => $cache_check['data']
    ];
  }

  // not found in cache -- fallback method
  $api_olid_search = api_olid_details($olid); // will use api_olid_details as a fallback
  if ($api_olid_search['status'] === 'fail' || empty($api_olid_search['data'])) {
    return [
      'status' => 'fail',
      'message' => 'Unable to find book details from API'
    ];
  }

  $book_details = $api_olid_search['data'];
  // $addedCount = 0; /s/ could count how many books were added for debugging if needed
  $cache_add = bookCache_add($book_details); // to update cache

  if (is_array(($cache_add)) && $cache_add['status'] === 'fail') {
    error_log("Failed to update cache for OLID {$olid}");
  }

  return [
    'status' => 'success',
    'data' => $book_details
  ];
}





// Cache Tables Pre-Populated via cron
function getRecentBooks()
{
  try {
    $mysqli = db();
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





// doBookRecommend ()
/*// RECOMMENDATION SYSTEM
// multiply the weight of the genre based on the books they have that of
// i.e. if they have a lot of books with the same genre in their library, the recommendation should reflect whatever genre weights heaviest; can assign counts
//1 - negative weight
//5 - positive weight
*/

// weighted recommendation resources
// https://www.kaggle.com/code/rafaymemon/building-recommendation-system-weighted-average
// https://global.php.cn/faq/536741.html

function doBookRecommend(array $req)
{
  // content-based filtering --> uses subjects to recommend a book

  // read olid of one book
  $olids = $req['olids'] ?? []; // check for olid or works_id
  if ($olids === '' || !is_array($olids))
    return ['status' => 'fail', 'message' => 'missing olid for query'];

  // collect ALL subjects' counts/weights
  $subjectCounts = [];

  foreach ($olids as $olid) {
    $olid = trim($olid); // clean the olid

    // check cache first
    $cache_check = bookCache_check_olid($olid);
    if ($cache_check['status'] === 'success') { // if book is found in the cache
      $cache_data = $cache_check['data'];
    } else {
      $olid_search = api_olid_details($olid); // manually get
      $olid_data = $olid_search['data'];
    }
  }
  // subjects should already be sanitized via api_endpoint's simple_sanitize


  // steps to recommend book (source: https://global.php.cn/faq/536741.html)

  // 1. data cleaning (already completed through any function in api_endpoints.php functions)



  // 2. data conversion --> make sure all OLIDs from library are strings within an array that is returned from my_library.php in /frontend



  // 3. data standardization --> data should be assigned a value within a range; professor recommended 1 (negative, lesser weight) to 5 (positive, higher weight)


  // 3a. weighted average formula --> SUM OF WEIGHTED ITEMS / TOTAL COUNT OF ITEMS

  // 3b. weighted score formular (for one item) --> SCORE * WEIGHT














  //shuffle($allSubjects);
  //print_r($allSubjects); // DEBUGGING


  // randomly select 2 subjects within the first 20 subjects
  $subject1 = $allSubjects[0]; // will get entered into subjects/{subject1}.json, PRIMARY SEARCH

  //echo "Primary subject: {$subject1}\n"; // DEBUGGING

  $subject2 = array_slice($allSubjects, 1, 20);

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
    ];
    break;
  }

  if (!$recommendedBook) {
    return ['status' => 'fail', 'message' => 'no recommendation found'];
  }

  echo "Found match: " . $olid . " || Matched with " . $rec_olid . " with subjects: \n" . implode(', ', $matchedSubject) . "\n";

  return [
    'status' => 'success',
    'recommended_book' => $recommendedBook
  ];


}



?>