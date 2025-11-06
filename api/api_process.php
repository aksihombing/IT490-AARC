<?php
require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/get_host_info.inc';

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


// bookCache(array $data)
function doBookCache(array $req)
{

  $type = $req['searchType'] ?? 'title';
  $query = strtolower(trim($req['query'] ?? ''));

  if ($query === '') return ['status' => 'fail', 'message' => 'missing query'];

  $limit = isset($req['limit']) && is_numeric($req['limit']) ? $req['limit'] : 10;
  $page = isset($req['page']) ? intval($req['page']) : 1;

  // CACHE CHECK ----------------------------------
  $mysqli = db();

  echo "Checking cache for: type={$type}, query='{$query}', limit={$limit}, page={$page}\n";

  $check_cache = $mysqli->prepare("SELECT * FROM library_cache WHERE search_type=? AND query=? AND pageNum=? AND expires_at > NOW() LIMIT ?");
  $check_cache->bind_param("ssii", $type, $query, $page, $limit);
  $check_cache->execute();
  $cache_result = $check_cache->get_result();

  if ($cache_result->num_rows > 0) {
    echo "Cache HIT for {$type}={$query}\n";
    $cachedData = [];

    while ($row = $cache_result->fetch_assoc()) {
      $cachedData[] = $row;
    }

    return ['status' => 'success', 'data' => $cachedData];
    // return cache HIT
  }


  // cache save
  echo "Saving to cache: type={$type}, query='{$query}'\n"; // debugging


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
  );

  $insertToTable->execute();
  return 

}

// doBookSearch ()
// use search.json
function doBookSearch (array $req){
  doBookCache($req)
  

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
    return ['status' => 'success', 'data' => $books];

  } catch (Exception $e) {
    error_log("getRecentBooks() error: " . $e->getMessage());
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



// doBookDetails () 
// combines all endpoints for accurate info
function doBookDetails(array $req){

}




// getRecentBooks ()





?>