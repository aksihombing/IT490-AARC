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

// SQL lookup query for user data
$stmt = $db->prepare("SELECT username, password FROM users WHERE username = ?"); // reminder : ? is the paramter placeholder lol
if ($stmt == false){ // DEBUGGING
	error_log("DB prepare statement for login.php failed.");
	exit(0);
}


$stmt->bind_param("s", $username); // referenced IT202 code for this part
if (!$stmt->execute()){
	// IF... query failed:
	error_log("DB execute statement for login.php failed.");
	$stmt->close();
	exit;
}
$stmt->store_result(); // holds data

if ($stmt->num_rows === 1) {
    // makes sure to limit matching results/rows to ONE only
    $stmt->bind_result($id, $dbUser, $dbHash);
    $stmt->fetch(); // fetch the row into $id, $dbUser, $dbHash
	// idk if we need id???

    // verify submitted password against hashed password from DB
    if (password_verify($password, $dbHash)) {
        $_SESSION['login'] = true;
        $_SESSION['uname'] = $dbUser;
        $_SESSION['uid']   = $id;

        echo json_encode(['status' => 'success', 'message' => 'Login successful']);
    } else {
        // wrong password
        echo json_encode(['status' => 'fail', 'message' => 'Invalid credentials']);
    }
} else {
    // no user found
    echo json_encode(['status' => 'fail', 'message' => 'Invalid credentials']);
}

echo json_encode($response);
//exit(0);

?>
