#!/usr/bin/php
<?php

$db = new mysqli('172.28.172.114','testUser','12345','testdb'); // creates new instance of mysql via php

if ($db->errno != 0)
{
	echo "failed to connect to database: ". $db->error . PHP_EOL;
	exit(0);
}

echo "successfully connected to database".PHP_EOL;

//$query = "select * from students;";

$response = $db->query($query);
if ($db->errno != 0)
{
	echo "failed to execute query:".PHP_EOL;
	echo __FILE__.':'.__LINE__.":error: ".$db->error.PHP_EOL;
	exit(0);
}


?>
