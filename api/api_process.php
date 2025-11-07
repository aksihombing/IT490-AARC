<?php
require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/get_host_info.inc';
require_once __DIR__ . '/api_endpoints.php';
// curl_get() + simple_sanitize() + api_search()
require_once __DIR__ . '/api_cache.php'; 
// db() + bookCache_check_query() + bookCache_check_olid() + bookCache_add()
// decides which function to run


// PROCESS API DATA -----------------------
// doBookSearch ()
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
    //removed offset
  ];


}

// doBookDetails () 
// combines all endpoints for accurate info
function doBookDetails(array $req)
{

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


function doBookRecommend(array $req)
{  // 1 to 1 book recommendation for the sake of speed
  // content-based filtering --> uses subjects to recommend a book
  // https://openlibrary.org/dev/docs/api/subjects



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



  $allSubjects_raw = array_map('strtolower', array_slice($work_data['subjects'] ?? [], 0, 50)); // grab nearly all subjects
  // NOTE : /works/ uses PLURAL 'subjects' ....
  $filteredSubjects = []; // filtered subjects, 1-word

  foreach ($allSubjects_raw as $filtering_subject) {
    $filtering_subject = strtolower(trim($filtering_subject)); // lowercase, trimmed

    // https://www.php.net/manual/en/function.preg-match.php
    // https://regexr.com/
    if (!preg_match('/^[a-z\-]+$/', $filtering_subject))
      continue;
    // one-word subjects, allowing for hyphenated subjects also
    // exclude anything with accented characters (beyond ascii char code 122)

    $filteredSubjects[] = $filtering_subject; // add good, single word subject to array
  }


  $allSubjects = array_slice($filteredSubjects, 0, 20); // not sure if slicing is needed here
  shuffle($allSubjects);
  //print_r($allSubjects); // DEBUGGING


  // randomly select 2 subjects within the first 20 subjects
  $subject1 = $allSubjects[0]; // will get entered into subjects/{subject1}.json, PRIMARY SEARCH

  //echo "Primary subject: {$subject1}\n"; // DEBUGGING

  $subject2 = array_slice($allSubjects, 1, 20);
  //skip first array item (which is used as the primary search), use next 20 subjects as a fallback in case there isnt a match with any one of them

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
    foreach ($rec_subjects_raw as $r_subject) {
      $r_subject = trim($r_subject);

      // same as previous filtering
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

  echo "Found match: " . $olid . " || Matched with " . $rec_olid . " with subjects: \n" . implode(', ', $matchedSubject) . "\n";

  return [
    'status' => 'success',
    'recommended_book' => $recommendedBook
  ];


}



?>