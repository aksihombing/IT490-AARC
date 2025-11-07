<?php

// HELPER FUNCTIONS ------------------------------
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


function simple_sanitize(array $list_raw)
{
  if (empty($list_raw))
    return [];

  //$list_raw = array_map('strtolower', array_slice($work_data['subjects'] ?? [], 0, 50)); // grab nearly all subjects
  // NOTE : /works/ uses PLURAL 'subjects' ....
  $filteredItems = []; // filtered subjects, 1-word

  foreach ($list_raw as $item) {
    $item = strtolower(trim($item)); // lowercase, trimmed
    // used regexr to help create regex filter
    if (!preg_match('/^[a-z\-]+$/', $item))
      continue;
    // one-word subjects, allowing for hyphenated subjects also
    // exclude anything with accented characters (beyond ascii char code 122)
    $filteredItems[] = $item; // add good, single word subject to array
  }
  return array_values(array_unique($filteredItems)); // returns everything that isnt repeated
}


// API DATA ------------------------------
function api_search(array $req)
{ // search for items based on title/author/general query
  // EXAMPLE URL https://openlibrary.org/search.json?q=harry%20potter&fields=key,title,author_name,first_publish_year&limit=1&page=1

  // to look at what ALL fields look like; use fields=* sparingly bc its intensive
  // https://openlibrary.org/search.json?q=harry+potter&fields=*&limit=1&page=1

  $query = $req['title']; // removed the "search by author" request type
  $limit = isset($req['limit']) && is_numeric($req['limit']) ? $req['limit'] : 10; // default to 10 if no limit specified
  $page = isset($req['page']) ? intval($req['page']) : 1; // default to 1 if no page specified

  $encodedQuery = urlencode($query); // url encodes query when its actually getting sent to the API
  $searchurl = "https://openlibrary.org/search.json?q={$encodedQuery}&fields=key,title,author_name,isbn,first_publish_year,ratings_average,ratings_count,subject_key,person_key,place_key,time_key,cover_i&limit={$limit}&page={$page}";


  $search_response = curl_get($searchurl);
  $search_data = json_decode($search_response, true);

  // what we need to find for each book and store in cache -- defaults if not found
  $olid = null;
  $title = 'Unknown title';
  $author = 'Unknown author';
  $isbn = null; // returns ALL isbns for ALL editions but honestly doesn't matter if its not THAT accurate for now
  $book_desc = 'No book description available'; // not returned from search endpoint
  $publish_year = null;
  $ratings_average = null;
  $ratings_count = null;
  $subjects = null;
  $person_key = null;
  $place_key = null;
  $time_key = null;
  $cover_url = null;

  $searchResults = []; // will return all books

  foreach ($search_data['docs'] as $book) { // FOREACH BOOK START
    // reading each doc that was returned
    $olid = str_replace('/works/', '', $book['key'] ?? null); // string
    $title = $book['title'] ?? 'Unknown title'; // string
    $author = $book['author_name'][0] ?? 'Unknown author'; //string
    $isbn = $book['isbn'][0] ?? [];
    $publish_year = $book['first_publish_year'] ?? []; // string
    $ratings_average = $book['ratings_average'] ?? [];
    $ratings_count = $book['ratings_count'] ?? [];
    $subjects = json_encode(simple_sanitize($book['subject_key'] ?? []));
    $person_key = json_encode($book['person_key'] ?? []);
    $place_key = json_encode($book['place_key'] ?? []);
    $time_key = json_encode($book['time_key']) ?? [];

    $cover_url = !empty($book['cover_i'])
      ? "https://covers.openlibrary.org/b/id/" . $book['cover_i'] . "-L.jpg" : null; // ternary -> if cover_i is set, then it saves the link
    // gets the -L (Large) version of the image


    $encodedOlid = urlencode($olid);

    // BOOK DESCRIPTION -----
    $work_url = "https://openlibrary.org/works/{$encodedOlid}.json";
    $work_json = curl_get($work_url);

    if ($work_json) {
      $work_data = json_decode($work_json, true); // decode to read all data
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
    }


    $searchResults[] = [ // this gets returned
      'page' => $page,
      'olid' => $olid,
      'title' => $title,
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

  } // END FOREACH BOOK

  return [
    'status' => 'success',
    'data' => $searchResults,
    'limit' => $limit,
    'page' => $page
  ];
}


?>