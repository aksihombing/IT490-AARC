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
    echo "invalid invite link";
    exit;
}

$client = new rabbitMQClient(__DIR__.'/../rabbitMQ/host.ini', 'ClubProcessor');
$res = $client->send_request(['type'=>'club.join_link','hash'=>$hash,'user_id'=>$user_id]);

if ($res['status']==='success') {
  echo "<p>you’ve successfully joined the book club!</p><a href=index.php?content=bookClub'>Back to Book Clubs</a>";
} else {
  echo "<p>failed to join club: ".$res['message'].". please try again</p>";
}

?>