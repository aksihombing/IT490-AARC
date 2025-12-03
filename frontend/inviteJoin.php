<?php
session_start();
require_once(__DIR__.'/../rabbitMQ/rabbitMQLib.inc');
require_once(__DIR__.'/../rabbitMQ/get_host_info.inc');

if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
$hash = $_GET['invite'] ?? '';

if (!$hash){
    echo '<div class="container mt-5"> <div class="alert alert-danger mb-2" role= "alert" invalid invite link';
    echo '<a href="index.php?content=bookClub" class="btn btn-dark">Back to Book Clubs</a></div></div>';
    exit;
}

$client = new rabbitMQClient(__DIR__.'/../rabbitMQ/host.ini', 'ClubProcessor');
$res = $client->send_request(['type'=>'club.join_link','hash'=>$hash,'user_id'=>$user_id]);

echo '<div class="container mt-5">';
if ($res['status']==='success') {
  echo '<div class="alert alert-success mb-2" role="alert"> you’ve successfully joined the book club!</div><a href=index.php?content=bookClub class="btn btn-dark">Back to Book Clubs</a>';
} else {
  echo "<div class='alert alert-danger mb-2' role='alert'> failed to join club: ".$res['message'] .". please try again</div><a href='index.php?content=bookClub' class='btn btn-dark'>Back to Book Clubs</a>";
}

?>