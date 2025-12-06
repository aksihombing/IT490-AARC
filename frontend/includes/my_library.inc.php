<?php
// Start session and check if user is logged in.
// If not, send them back to login page.
// FROM CHIZZY
// edited by Rea

// DEBUGGING
error_reporting(E_ALL);
ini_set('display_errors', 1);


//session_start();
require_once __DIR__ . '/../../rabbitMQ/rabbitMQLib.inc';


$userId = $_SESSION['user_id'];// getting the user id from the session
$error = ''; // idk if we need this

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $worksID = $_POST['works_id'] ?? '';
    $client = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'LibraryPersonal');
    $client->send_request([
      'type' => 'library.personal.remove',
      'user_id' => $userId,
      'works_id' => $worksID

    ]);

    header('Location: /index.php?content=my_library');
    exit;
  } catch (Exception $e) {
    $error = "Error connecting to library: " . $e->getMessage();

  }

}


function getPLibDetails($plib_olid)
{
  try {
    $bookDetailsClient = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'LibraryDetails');

    $response = $bookDetailsClient->send_request([
      'type' => 'book_details',
      'olid' => $plib_olid
    ]);

    if (($response['status'] === 'success') && isset($response['data']) && is_array($response['data'])) { // checks success, if data is set, and if data is array
      $plib_bookdata = $response['data'];
      return [
        'olid' => $plib_olid,
        'title' => $plib_bookdata['title'] ?? 'Unknown Title',
        'author' => $plib_bookdata['author'] ?? 'Unknown Author',
        //'isbn' => $plib_bookdata['isbn'] ?? 'N/A',
        'cover_url' => $plib_bookdata['cover_url'] ?? 'default-cover.png', // fallback if missing
        'publish_year' => $plib_bookdata['publish_year'] ?? 'Unknown'
      ];

    } else {
      return null; // if theres no books in library ?
    }
  } catch (Exception $e) {
    return "Error connecting to personal library service: " . $e->getMessage(); // idk what to return here
  }
}

function getRecommendation(array $library_books)
{
  // maybe could use a retry if fail loop or something
  try {
    $bookRecommendClient = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'LibraryCollect');

    $response = $bookRecommendClient->send_request([
      'type' => 'book_recommend',
      'olids' => $library_books // send ALL olids
    ]);

    if (($response['status'] === 'success') && isset($response['data']) && is_array($response['data'])) { // checks success, if data is set, and if data is array
      $rec_bookdata = $response['data'];
      log_event("frontend", "success", "Successfully returned user's personal library books.");
      return $rec_bookdata;
      /*return [
        'olid' => $rec_bookdata['olid'],
        'title' => $rec_bookdata['title'] ?? 'Unknown Title',
        'author' => $rec_bookdata['author'] ?? 'Unknown Author',
        //'isbn' => $rec_bookdata['isbn'] ?? 'N/A',
        'cover_url' => $rec_bookdata['cover_url'] ?? '', // no fallback actually implemented yet
        'publish_year' => $rec_bookdata['publish_year'] ?? 'Unknown'
      ];*/

    } else {
      log_event("frontend", "success", "Successfully returned user's personal library books.");
      return []; // if theres no books in library ?
    }

  } catch (Exception $e) {
    log_event("frontend", "error", "Error connecting to personal library service: " . ($e->getMessage()));
    return "Error connecting to personal library service: " . $e->getMessage(); // idk what to return here
  }
}



// RUNS WHEN THE PAGE LOADS !! ------------

// libraryOlidList -> all olids from the personal library
// libraryBooks -> book details for all olids

$libraryOlidList = []; // for all olids in the library

try {
  $bookListClient = new rabbitMQClient(__DIR__ . '/../../rabbitMQ/host.ini', 'LibraryPersonal');
  $resp = $bookListClient->send_request([
    'type' => 'library.personal.list',
    'user_id' => $userId,

  ]);
  //echo "<p>" . print_r($resp, true) . "</p>"; // DEBUGGING - checking response
  if ($resp['status'] === 'success') {
    $libraryOlidList = $resp['items'];
  } else {
    $error = $resp['message'] ?? 'Unknown error from server.';
  }
} catch (Exception $e) {
  $error = "Error connecting to library service: " . $e->getMessage();
}

// after the library is loaded ..
$libraryBooks = [];
$recommendedBooks = [];

if (!empty($libraryOlidList)) {  // if library isnt empty
  $library_olids = []; // i think $libraryOlidList stores it as ['olid' => OLID]; we need just a clean list of olids
  foreach ($libraryOlidList as $singleBook) { // each book in library loop

    // GET BOOK DETAILS to display on page
    $olid = $singleBook['olid'] ?? $singleBook['works_id'] ?? $singleBook;
    $library_olids[] = $olid;

    $details = getPLibDetails($olid);
    if ($details) {
      $libraryBooks[] = $details; // adds book details in an array per olid
      //echo "<p>getPLibDetails foreach:" . print_r($libraryBooks, true) . "</p>"; // DEBUGGING - checking response
    }

  } // end of foreach for getting details of each book

  // GET BOOK RECOMMENDATION -  Library_API should already give all necessary info per book
  $recommendedBooks = getRecommendation($library_olids);
  /*if ($recommendations) {
    $recommendedBooks[] = $recommendations; // adds book details in an array per olid
    //echo "<p>getPLibDetails foreach:" . print_r($libraryBooks, true) . "</p>"; // DEBUGGING - checking response
  }*/

}

?>