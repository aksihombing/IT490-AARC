/*
This page will allow for all data management such as create clubs, invite members, and it is 
managed by sending requests to the rabbitmq listener 

The front-end uses $userID as well as ot help eith the db

The dbs' table is being used like the club, club members, and club events
*/

<?php

session_start();
$is_logged_in = isset($_SESSION['login']);
$userID = $_SESSION['userID'] ?? null;
$username = $_SESSION['username'] ?? 'Guest';

if (!$userID) {
    header("Location: login.php?error=Please log in to access this Page.");
    exit();
}
require_once __DIR__ . '../rabbitMQ';


?>



