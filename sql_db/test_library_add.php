<?php
require_once __DIR__.'/../rabbitMQ/rabbitMQLib.inc';
session_start(); $_SESSION['user_id'] = 12; // temp

$olid1 = 'OL86318W';
$olid2 = 'OL86701W';

$client = new rabbitMQClient(__DIR__.'/../rabbitMQ/host.ini','LibraryPersonal');

error_log("→ add $olid1");
var_dump($client->send_request(['type'=>'library.personal.add','user_id'=>$_SESSION['user_id'],'works_id'=>$olid1]));

error_log("→ add $olid2");
var_dump($client->send_request(['type'=>'library.personal.add','user_id'=>$_SESSION['user_id'],'works_id'=>$olid2]));
