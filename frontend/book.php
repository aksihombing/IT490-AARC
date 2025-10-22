<?php
session_start();
/*
CHIZZYS CODE
edited by Rea


if (!isset($_SESSION['session_key'])) { header("Location: main.inc.php"); exit; }
*/
$work = $_GET['works_id'] ?? '';
if ($work === '') { header("Location: index.php?content=search"); exit; }

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Book Details</title>
  <link rel="stylesheet" href="/css/book.css">
</head>
<body>
<h2 id="book-title">Book Title</h2>
<p id="book-author">by Author Name</p>

<div class="book-info">
  <img id="cover" class="cover" alt="Book Cover">
  <div id="book-data">Book details go here (release, genre, etc.)</div>
</div>

<button id="addToLib" class="btn">Add to My Library</button>

<section>
  <h3>Write a Review</h3>
  <form id="reviewForm">
    <label>Rating:
      <select id="rating" required>
        <option value="">Select...</option>
        <option>1</option><option>2</option><option>3</option><option>4</option><option>5</option>
      </select>
    </label>
    <br>
    <label>Review:</label><br>
    <textarea id="comment" rows="3" placeholder="Write your thoughts here..."></textarea>
    <br>
    <button class="btn" type="submit">Submit</button>
  </form>
</section>

<section>
  <h3>User Reviews</h3>
  <div id="reviews"></div>
</section>



</body>
</html>




