<?php
// Start session and check if user is logged in.
// If not, send them back to login page.
// FROM CHIZZY
// edited by Rea
session_start();
/*
index should already be checking session keys
if (!isset($_SESSION['session_key'])) { 
  //header("Location: main.inc.php"); 
  header("Location: index.php"); // 
  exit; 
}
  */
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>My Library</title>
  <!-- linking your external CSS file for styling -->
  <link rel="stylesheet" href="/css/library.css">
</head>
<body>
  <!-- Page heading -->
  <h2>My Library</h2>

  <!-- Message that shows up when a user has no saved books, will go to the search page assuming its called search??? -->
  <div id="empty" class="empty" style="display:none;">
    Your library is empty. <a href="index.php?content=search">Search for books →</a>
  </div>

  <!-- This grid will hold all the book cards -->
  <div id="grid" class="grid"></div>

</body>
</html>

