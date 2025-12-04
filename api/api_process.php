<?php
require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/get_host_info.inc';
require_once __DIR__ . '/api_endpoints.php';
require_once __DIR__ . '/log_producer.php';
// curl_get() + simple_sanitize() + api_search()
// decides which function to run


// PROCESS API DATA -----------------------
// use search.json
function doBookSearch(array $req)
{
  // cache MISS
  $search_api = api_search($req);

  if ($search_api['status'] === 'fail') {
    log_event('dmz', 'fail', 'doBookSearch ' . $req['query']);
    return [
      "status" => "fail",
      "message" => "No results found"
    ];
  }

  $books = $search_api['data'];
  // $addedCount = 0; // could count how many books were added for debugging if needed

  log_event('dmz', 'success', 'doBookSearch: ' . $req['query']);
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

  // not found in cache -- fallback method
  $api_olid_search = api_olid_details($olid); // will use api_olid_details as a fallback
  if ($api_olid_search['status'] === 'fail' || empty($api_olid_search['data'])) {
    log_event('dmz', 'fail', 'doBookDetails unable to gather api_olid_details');
    return [
      'status' => 'fail',
      'message' => 'Unable to find book details from API'
    ];
  }

  $book_details = $api_olid_search['data'];
  // $addedCount = 0; /s/ could count how many books were added for debugging if needed

  log_event('dmz', 'success', 'doBookDetails: ' . $olid);
  return [
    'status' => 'success',
    'data' => $book_details
  ];
}





// doBookRecommend ()

function doBookRecommend(array $req)
{
  // content-based filtering --> uses subjects to recommend a book

  // read olid of one book
  $olids = $req['olids'] ?? []; // check for olid or works_id
  if ($olids === '' || !is_array($olids))
    return ['status' => 'fail', 'message' => 'missing olid for query'];

  // collect ALL subjects' counts/weights
  $subjectCounts = [];
  $totalBooks = count($olids);


  // subjects should already be sanitized via api_endpoint's simple_sanitize
  // general steps to recommend book (source: https://global.php.cn/faq/536741.html)
  // data cleaning (already completed through any function in api_endpoints.php functions)


  foreach ($olids as $olid) { // FOR EACH BOOK : 
    $olid = trim($olid); // clean the olid


    // data conversion --> make sure all OLIDs from library are strings within an array that is returned from my_library.php in /frontend
    // check cache first

    $olid_search = api_olid_details($olid); // manually get
    $bookData = $olid_search['data'];
    $subjects = $bookData['subjects'];

    // read subjects and decode if it's a json (i.e. if it was returned from the database), not sure if needed anymore but its here just in case
    if (is_string($subjects)) {
      $subjects_decoded = json_decode($subjects);
      $subjects = is_array($subjects_decoded) ? $subjects_decoded : [];
    }


    // normalize subjects (sanitize isn't needed because _endpoints does it already)
    foreach ($subjects as $subject) {
      if ($subject === '')
        continue; //skip if empty, just in case

      if (!isset($subjectCounts[$subject])) { // add to counts if not already there
        $subjectCounts[$subject] = 1;
      } else {
        $subjectCounts[$subject]++; // update count if subject is repeated
      }
    }
  } // end foreach

  // if subjects empty still
  if (empty($subjectCounts)) {
    return [
      "status" => "error",
      "message" => "No subjects found"
    ];
  }

  // normalize on a scale of 1 to 5, as recommended by prof
  // normalization is needed because it takes into account the user's library size and why some subjects may appear more than others
  $subjectWeights = [];
  foreach ($subjectCounts as $subject => $count) { // foreach key => value subjectCount pair
    $normalizedScore = $count / $totalBooks; // frequency percentage
    $scaledWeight = round($normalizedScore * 5, 2); // get scaled weight of the score; need to round the percentage
    $subjectWeights[$subject] = $scaledWeight;
  }



  arsort($subjectWeights); // sort weighted subject array

  $topSubjects = array_slice(array_keys($subjectWeights), 0, 5); // get top 5 subjects, which are stored as keys in the array
  echo "ALL Top Subjects: " . implode(',', $topSubjects) . "\n"; // DEBUGGING
  $topSubject1 = $topSubjects[0]; // the anchor
  //echo "Top Subject: {$topSubject1}\n"; // DEBUGGING
  $secondarySubjects = array_slice($topSubjects, 1); // remove the first one
  //echo "Secondary Subjects: " . implode(',' , $secondarySubjects) . "\n"; // DEBUGGING


  //print_r($topSubjects); // DEBUGGING

  $recommendedBooks = [];

  $encodedTopSubject = urlencode($topSubject1);

  // could move this part into api_endpoints but lazy
  // reused logic from previous version of rec system
  $subjectUrl = "https://openlibrary.org/subjects/{$encodedTopSubject}.json?sort=rating%20desc&limit=50";

  $subject_json = curl_get($subjectUrl);
  $subject_data = json_decode($subject_json, true);
  $works = $subject_data["works"] ?? [];

  if (isset($works)) {
    foreach ($works as $oneBook) {
      $bookSubjects = [];
      if (!empty($oneBook['subject'])) { //clean subjects and add to array
        $bookSubjects = simple_sanitize($oneBook['subject']);
      }

      $matchedSubjects = array_intersect($secondarySubjects, $bookSubjects);
      $matchCount = count($matchedSubjects);

      // if we find a book with matches, add to recommendedBooks array
      if ($matchCount > 0) {
        $rec_olid = str_replace('/works/', '', $oneBook['key'] ?? '');

        if (in_array($rec_olid, $olids, true))
          continue;
        // need to skip a possible recommendation if its already in the array of olids from user's library

        $recommendedBooks[] = [
          'olid' => $rec_olid,
          'title' => $oneBook['title'] ?? 'Unknown',
          'author' => $oneBook['authors'][0]['name'] ?? 'Unknown',
          'publish_year' => $oneBook['first_publish_year'] ?? null,
          'cover_url' => isset($oneBook['cover_id']) // note: stored as cover_id and not cover_i via subjects endpoint
            ? "https://covers.openlibrary.org/b/id/" . $oneBook['cover_id'] . "-L.jpg"
            : null,

          // recommendation system data
          'anchor_subject' => $topSubject1,
          'matched_subjects' => array_values($matchedSubjects),
          'match_score' => $matchCount
        ];
      }
    } // end foreach
  } // end if for reading json results

  // DEBUGGING
  //   echo "Found match: " . $olid . " || Matched with " . $rec_olid . " with subjects: \n" . implode(', ', $matchedSubject) . "\n";

  if (!$recommendedBooks) {
    return ['status' => 'fail', 'message' => 'no recommendation found'];
  }

  log_event('dmz', 'success', 'doBookRecommend: Matched [' . $olids . '] with ' . count($recommendedBooks) . ' books. Top subjects: ' . $topSubjects);

  return [
    'status' => 'success',
    'data' => $recommendedBooks
  ];


}



?>