<?php

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

// -------------- What needs to be broken up --------------
// keep in separate script file for easier updating

// /search.json?q=XYZ&fields=x,y,z&limit=1
function searchEndpoint(array $req)
{
  $encodedQuery = urlencode($title); // url encodes query when its actually getting sent to the API
  $searchurl = "https://openlibrary.org/search.json?q={$encodedQuery}&limit=1";

  $search_response = curl_get($searchurl);
  $search_data = json_decode($search_response, true);


  $author = 'Unknown author';
  $subtitle = null;
  $publish_year = null;
  $cover_url = null;

  if ($search_data && isset($search_data['docs'][0])) { // get first doc only
    $doc = $search_data['docs'][0];
    $subtitle = $doc['subtitle'] ?? null; //string
    $author = $doc['author_name'][0] ?? 'Unknown author'; //string
    $publish_year = $doc['first_publish_year'];
    $cover_url = !empty($doc['cover_i'])
      ? "https://covers.openlibrary.org/b/id/" . $doc['cover_i'] . "-L.jpg" : null; // ternary -> if cover_i is set, then it saves the link

    // gets the -L (Large) version of the image

  }
}




// /works/olid.json?
function worksEndpoint(array $req)
{
  $work_url = "https://openlibrary.org/works/{$olid}.json";
  $work_json = curl_get($work_url);

  $book_desc = 'No book description available';
  $subjects = null;
  $person_key = null;
  $place_key = null;
  $time_key = null;

  if ($work_json) {
    $work_data = json_decode($work_json, true); // decode to read all data
    $title = $work_data['title'] ?? 'Unknown title';


    if (isset($work_data['description'])) {
      if (is_array($work_data['description'])) {
        $book_desc = $work_data['description']['value'];
      } elseif (is_string($work_data['description'])) {
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

  }
}




// /works/olid/editions.json?
function editionsEndpoint(array $req)
{ // need olid
  $editions_url = "https://openlibrary.org/works/{$olid}/editions.json?limit=1"; // only get 1 of the editions isbn
  $editions_json = curl_get($editions_url);

  $isbn = null;
  if ($editions_json) {
    $editions_data = json_decode($editions_json, true);
    $first_entry = $editions_data['entries'][0]; // gets the first entry result

    if (!empty($first_entry['isbn_13'][0])) {
      $isbn = $first_entry['isbn_13'][0];
    } elseif (!empty($first_entry['isbn_10'][0])) {
      $isbn = $first_entry['isbn_10'][0];
    } else {
      $isbn = null; // no isbn found
    }
  }
}




// /works/olid/ratings.json?
function ratingsEndpoint(array $req)
{
  // needs olid
  $ratings_average = null;
  $ratings_count = null;
  $ratings_url = "https://openlibrary.org/works/{$olid}/ratings.json";
  $ratings_json = curl_get($ratings_url);
  if ($ratings_json) {
    $ratings_data = json_decode($ratings_json, true);
    $ratings_average = $ratings_data['summary']['average'] ?? null;
    $ratings_count = $ratings_data['summary']['count'] ?? null;
  }
}


// /subject.json
function subjectEndpoint(array $req)
{
  // needs olid
  $work_url = "https://openlibrary.org/works/{$olid}.json";
  $work_json = curl_get($work_url);

  $book_desc = 'No book description available';
  $subjects = null;
  $person_key = null;
  $place_key = null;
  $time_key = null;

  if ($work_json) {
    $work_data = json_decode($work_json, true); // decode to read all data


    if (is_array($work_data['description'])) { // still getting a php warning idky
      $book_desc = $work_data['description']['value'];
    } elseif (is_string($work_data['description'])) {
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
}




?>