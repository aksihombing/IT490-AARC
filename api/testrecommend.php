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
    print_r($allSubjects); // DEBUGGING


    // randomly select 2 subjects within the first 20 subjects
    $subject1 = $allSubjects[0]; // will get entered into subjects/{subject1}.json, PRIMARY SEARCH

    echo "Primary subject: {$subject1}\n"; // DEBUGGING

    $subject2 = array_slice($allSubjects, 1, 20); //skip first array item (which is used as the primary search), use next 20 subjects as a fallback in case there isnt a match with any one of them
    echo "Second subject options: \n"; // DEBUGGING
    var_dump($subject2);

    // subjects/{subject}.json search query
    // search results for another book in "works" that has a "subject" item equal to $random_subjects[1] --> Only recommend first match
    $encodedSubject1 = urlencode($subject1);
    //$subjectUrl = "https://openlibrary.org/search.json?subject={$encodedSubject1}&limit=40";
    $subjectUrl = "https://openlibrary.org/subjects/{$encodedSubject1}.json?limit=50";
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

        echo "filtered rec_subjects:";
        print_r($rec_subjects); // DEBUGGING
        //https://www.php.net/manual/en/function.array-intersect.php


        $matchedSubject = array_intersect($rec_subjects, $subject2);
        echo "found matched subject" . $matchedSubject ."\n"; // DEBUGGING
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