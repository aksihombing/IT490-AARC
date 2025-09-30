<?php
session_start();
// need to connect to sql db somehow ***

if (!isset($_POST)) // if POST is not set 
{
	$msg = "NO POST MESSAGE SET";
	echo json_encode($msg);
	exit(0);
}
$request = $_POST;
$response = "Unsupported Request. Denied!"; // failed request


$username = $_POST['username'];
$password = $_POST['password'];


 $request = [
        'action'   => 'login',
        'username' => $username,
        'password' => $password,

    ];

try {
    $client = new rabbitMQClient("testRabbitMQ.ini","testServer"); 

    // NEED TO PUSH DATA THROUGH RABBIT, NOT DIRECTLY TO DB

    $response = $client->send_request($request);

    if ($response == false) { // if no response from RabbitMQ...
        echo "Registration failed. No response from server";
    }
    else {
        echo "Server Responded! : " . print_r($response,true);
    }


} catch (Exception $e) {
    echo "Error connecting to RabbitMQ: " . $e->getMessage();
}


//exit(0);

?>
