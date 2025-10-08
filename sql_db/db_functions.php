<?php
// altered from mysqlconnect.php
// contains database server's info for connection.

function getDBConnection(){
    $dbServerIP = "172.28.172.114";
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


?>