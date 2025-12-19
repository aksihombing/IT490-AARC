<?
require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/get_host_info.inc';
// just in case
function apidb()
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


// ---- CACHE DATABASE ----

// bookCache(array $data)
function bookCache_check_query(array $req) // check cache book ONE AT A TIME
{
    try {
        $type = $req['searchType'] ?? 'title';
        $query = strtolower(trim($req['query'] ?? ''));

        if ($query === '')
            return ['status' => 'fail', 'message' => 'missing query'];

        $limit = isset($req['limit']) && is_numeric($req['limit']) ? (int) $req['limit'] : 10;
        $page = isset($req['page']) ? intval($req['page']) : 1;

        // CACHE CHECK ----------------------------------
        $mysqli = apidb();

        echo "Checking cache for: type={$type}, query='{$query}', limit={$limit}, page={$page}\n";

        // check for search_type, query, page_num AND check if expired.
        $check_cache = $mysqli->prepare("SELECT * FROM library_cache WHERE search_type=? AND query=? AND page_num=? AND expires_at > NOW() LIMIT ?");
        $check_cache->bind_param("ssii", $type, $query, $page, $limit);
        $check_cache->execute();
        $cache_result = $check_cache->get_result();

        if ($cache_result->num_rows > 0) {
            echo "Cache HIT for {$type}={$query}\n";
            $cachedData = [];

            while ($row = $cache_result->fetch_assoc()) {
                $cachedData[] = $row;
            }
            $mysqli->close();
            return [
                'status' => 'success',
                'data' => $cachedData
            ];
            // return cache HIT
        } else {
            $mysqli->close();
            return [
                'status' => 'fail' // could be expired OR not in cache
            ];
        }
    } catch (Exception $e) {
        return [
            'status' => 'fail',
            'message' => "Error processing request: " . $e->getMessage()
        ];
    }
}





// book search by OLID
function bookCache_check_olid(string $olid) // check cache book ONE AT A TIME
{
    try {
        $mysqli = apidb();

        echo "Checking cache for: olid = {$olid}\n";

        // check for olid, does NOT check if it is expired, but it should be fine
        $check_cache = $mysqli->prepare("SELECT * FROM library_cache WHERE olid=?");
        $check_cache->bind_param("s", $olid);
        $check_cache->execute();
        $cache_result = $check_cache->get_result();

        if ($cache_result->num_rows > 0) {
            echo "Cache HIT for OLID {$olid}\n";
            $cachedData = $cache_result->fetch_assoc();

            $mysqli->close();
            return [
                'status' => 'success',
                'data' => $cachedData
            ];
            // return cache HIT
        } else {
            $mysqli->close();
            echo "Did not find {$olid} in cache\n";
            return [
                'status' => 'fail' // could be expired OR not in cache
            ];
        }
    } catch (Exception $e) {
        return [
            'status' => 'fail',
            'message' => "Error processing request: " . $e->getMessage()
        ];
    }
}




// add to cache
function bookCache_add(array $req) // add book ONE AT A TIME
{
    $mysqli = apidb();
    $insertToTable = $mysqli->prepare("
    INSERT INTO library_cache (
      search_type, query, page_num, olid, title, author, isbn,
      book_desc, publish_year, ratings_average, ratings_count,
      subjects, person_key, place_key, time_key, cover_url
    )
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
      title=VALUES(title),
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
    try {
        // read array

        $type = $req['searchType'] ?? 'title';
        $query = strtolower(trim($req['query'] ?? ''));
        $page = isset($req['page']) ? intval($req['page']) : 1; // default to 1 if no page specified

        $olid = $req['olid'] ?? [];
        $title = $req['title'] ?? 'Unknown title';
        $author = $req['author'] ?? 'Unknown author';
        $isbn = $req['isbn'] ?? []; // returns ALL isbns for ALL editions but honestly doesn't matter if its not THAT accurate for now
        $book_desc = $req['book_desc'] ?? 'No book description available'; // not returned from search endpoint
        $publish_year = $req['publish_year'] ?? [];
        $ratings_average = $req['ratings_average'] ?? [];
        $ratings_count = $req['ratings_count'] ?? [];
        $subjects = $req['subjects'] ?? [];
        $person_key = $req['person_key'] ?? [];
        $place_key = $req['place_key'] ?? [];
        $time_key = $req['time_key'] ?? [];
        $cover_url = $req['cover_url'] ?? [];

        // cache save
        echo "Saving to cache: type={$type}, query='{$query}'\n"; // debugging


        $insertToTable->bind_param(
            "ssisssssidisssss",
            $type, // string
            $query, // string
            $page, // int
            $olid, // string
            $title, // string
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
        $mysqli->close();
        return ['status' => 'success']; // to verify completion
    } catch (Exception $e) {
        $mysqli->close();
        return [
            'status' => 'fail',
            'message' => "Error processing request: " . $e->getMessage()
        ];
    }
}






// Cache Tables Pre-Populated via cron
function getRecentBooks()
{
    try {
        $mysqli = apidb();
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


// to send requests to API/DMZ to update itself

function doBookCollect(array $req)
{
    $requestType = $req['type'] ?? null;
    // check internal cache for details --> depends on if the request type was book_search or book_details
    $error = null; // backup error checking since so many things can go wrong
    $responseData = null;

    switch ($requestType) {
        case 'book_search':
            $cache_check = bookCache_check_query($req);
            if ($cache_check['status'] === 'success') {
                $responseData = $cache_check['data'];
            } else {
                echo "Cache MISSED for book_search -- calling DMZ\n";
                $req['type'] = 'api_book_search';
                // had to update type so that the library listener LISTENS for this request type

                $client = new rabbitMQClient(__DIR__ . "/../rabbitMQ/host.ini", "LibrarySearch");

                // update request 'type' to api_book_search or api_book_details
                $DMZresponse = $client->send_request($req); // response from dmz
                //var_dump($response); //debugging 

                if ($DMZresponse['status'] === 'success' && !empty($DMZresponse['data'])) {
                    $responseData = $DMZresponse['data'];
                    foreach ($responseData as $book) {
                        bookCache_add($book); // update internal apidb with ALL books that are returned
                    }
                } else {
                    $error = $DMZresponse['message'] ?? 'Unknown error from server.';
                }
            }
            break;



        case 'book_details':
            $cache_check = bookCache_check_olid($req['olid']);
            if ($cache_check['status'] === 'success') {
                $responseData = $cache_check['data'];
            } else {
                echo "Cache MISSED for book_details -- calling DMZ\n";
                $req['type'] = 'api_book_details';
                // had to update type so that the library listener LISTENS for this request type

                $client = new rabbitMQClient(__DIR__ . "/../rabbitMQ/host.ini", "LibraryDetails");

                // update request 'type' to api_book_search or api_book_details
                $DMZresponse = $client->send_request($req); // response from dmz
                //var_dump($response); //debugging 

                if ($DMZresponse['status'] === 'success' && !empty($DMZresponse['data'])) {
                    $responseData = $DMZresponse['data'];
                    bookCache_add($responseData); // only one book detail is returned at a time
                }
                else {
                    $error = $DMZresponse['message'] ?? 'Unknown error from server.';
                }
            }
            break;

        default:
            return [
                "status" => "error",
                "message" => "Request error in library_process"
            ];
    } // end switch

    if ($responseData) {
        return [
            'status' => 'success',
            'data' => $responseData
        ];
    } else {
        return [
            "status" => "error",
            "message" => $error ?? "No results from library API found"
        ];
    }

}




?>