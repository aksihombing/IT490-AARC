<?php


// ---------------------------------------------------------
/* NO LONGER NEEDED ?? full of errors



// /works/olid.json?
function worksEndpoint(array $req)
{
  $encodedOlid = urlencode($req['olid']);
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
  $encodedOlid = urlencode($req['olid']);
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
*/



?>


