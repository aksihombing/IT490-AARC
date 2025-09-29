<?php


if (!isset($_POST)) // if POST is not set 
{
	$msg = "NO POST MESSAGE SET";
	echo json_encode($msg);
	exit(0);
}
$request = $_POST;
$response = "Unsupported Request. Denied!"; // failed request
switch ($request["type"])
{
	case "login":
		$response = "Login Success."; // response is updated if request is okay
	break;
}
echo json_encode($response);
exit(0);

?>
