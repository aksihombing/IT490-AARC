<?php
// Created by Rea S.
/*
<?php
session_start();
$_SESSION = [];
session_destroy();
header('Location: index.php');
exit;
*/ //Maybe this can work as a logout as well?
if (isset($_SESSION['login'])) { // CHECK IF LOGIN SESSION IS SET
   /*unset($_SESSION['login']);
   unset($_SESSION['emailAddress']);
   unset($_SESSION['firstName']);
   unset($_SESSION['lastName']);
   unset($_SESSION['pronouns']);*/
   // What log-in parameters are we setting?
}
// Logout button
/*
if (headers_sent()) {
   echo "<h2>Click <a id='logoutIF' href=\"index.php?content=logout\"><strong>here</strong></a> to logout.</h2>";
 } else {
   header("Location: index.php");
 }
 */
?>
