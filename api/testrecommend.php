<?php 


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
  $allSubjects = array_map('strtolower', array_slice($work_data['subjects'] ?? [], 0, 20));// get all subjects returned, limit to first 20 subjects and makes sure its all lowercase
  shuffle($allSubjects); // easier to select a random selection of two subjects to search with

  // randomly select 2 subjects within the first 20 subjects
  $subject1 = $allSubjects[0]; // will get entered into subjects/{subject1}.json, PRIMARY SEARCH

  echo "Primary subject: {$subject1}\n"; // DEBUGGING

  $subject2 = array_slice($allSubjects, 1, 20); //skip first array item (which is used as the primary search), use next 20 subjects as a fallback in case there isnt a match with any one of them
  echo "Second subject options: {$subject2}\n"; // DEBUGGING

  // subjects/{subject}.json search query
  // search results for another book in "works" that has a "subject" item equal to $random_subjects[1] --> Only recommend first match
  $encodedSubject1 = urlencode($subject1);
  //$subjectUrl = "https://openlibrary.org/search.json?subject={$encodedSubject1}&limit=40";
  $subjectUrl = "https://openlibrary.org/subjects/{$encodedSubject1}.json?limit=50";
  $subject_json = curl_get($subjectUrl);
  $subject_data = json_decode($subject_json, true);
  $docs = $subject_data["docs"] ?? [];

  $recommendedBook = null;


  // return recommended book's olid --> maybe return 
  foreach ($docs as $oneBook) {

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
    $docSubjects = array_map('strtolower', $oneBook['subjects'] ?? []);

    echo "strtolower docSubjects: {$docSubjects}\n"; 
    //https://www.php.net/manual/en/function.array-intersect.php
    $matchedSubject = array_intersect($docSubjects, $subject2);
    if (empty($matchedSubject))
      continue; // goes to next iteration until match found

    // should only reach this area if a return is found
    $recommendedBook = [
      'olid' => $rec_olid,
      'title' => $oneBook['title'] ?? 'Unknown',
      'author' => $oneBook['author_name'][0] ?? 'Unknown',
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

  return [
    'status' => 'success',
    'recommended_book' => $recommendedBook
  ];


}

// running the results
header('Content-Type: application/json');
$testResult = doBookRecommend(['olid' => 'OL82548W']);
echo json_encode($testResult, JSON_PRETTY_PRINT);
?>