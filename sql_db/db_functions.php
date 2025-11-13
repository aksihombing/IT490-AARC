<?php
// https://www.sitepoint.com/users-php-sessions-mysql
// https://www.w3schools.com/php/php_sessions.asp

// -------- getDBConnection --------
// altered from mysqlconnect.php
// contains database server's info for connection.

/// REA'S CODE
// scrapped
// Aida's Server.php file integrates it already

function getDBConnection(){
    $dbServerIP = "172.28.109.126"; // Rea's Zero-Tier IP
    $dbUsername = "testUser";
    $dbPassword = "12345";
    $dbName = "testdb";
    $conn = new mysqli($dbServerIP,$dbUsername,$dbPassword,$dbName);

    // Error Handling : DB Connection Issue
    if ($conn->errno != 0)
    {
        echo "failed to connect to database: ". $conn->error . PHP_EOL;
        exit(0);
    }

    echo "successfully connected to database".PHP_EOL;

}


// -------- SESSIONS --------
function createSession($user_id, $conn){
    $session_key = bin2hex(random_bytes(16)); // 32-character hex session key

    $stmt = $conn->prepare("INSERT INTO sessions (user_id, session_key) VALUES (?, ?)");
    $stmt->bind_param("is",$user_id,$session_key);

    if ($stmt->execute()){
        return $session_key; // if successful in inserting session key, return session key to whoever is calling the function
    }
    else{
        error_log("DB Error: Failed to insert session key for user $user_id - " . $conn->error);
        return null;
    }

}

function validateSession($session_key,$conn){
    $stmt = $conn->prepare("SELECT user_id FROM sessions WHERE session_key = ?");
    $stmt->bind_param("s", $session_key);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1){ // if session is found, it means its valid
        $row = $result->fetch_assoc();
        return $row['user_id'];
    }
    
    return null; // returns null if its unable to validate
}


?>