<?php
// Optional includes if present
if (file_exists(__DIR__ . '/path.inc')) {
    require_once __DIR__ . '/path.inc';
}
if (file_exists(__DIR__ . '/get_host_info.inc')) {
    require_once __DIR__ . '/get_host_info.inc';
}
if (file_exists(__DIR__ . '/rabbitMQLib.inc')) {
    require_once __DIR__ . '/rabbitMQLib.inc';
}

function get_db_connection(): ?\mysqli {
    $host = '127.0.0.1';
    $user = 'testUser';
    $pass = 'userPass';
    $db   = 'userdb';

    $mydb = new mysqli($host, $user, $pass, $db);
    if ($mydb->connect_errno) {
        error_log('Failed to connect to the database: ' . $mydb->connect_error);
        return null;
    }
    $mydb->set_charset('utf8mb4');
    return $mydb;
}

    // New safe helper: returns mysqli or null (no exit) so the worker can call it
    function get_db_connection_new(): ?\mysqli {
        $host = '127.0.0.1';
        $user = 'testUser';
        $pass = 'userPass';
        $db   = 'userdb';

        $mydb = new mysqli($host, $user, $pass, $db);
        if ($mydb->connect_errno) {
            error_log('DB connect failed: ' . $mydb->connect_error);
            return null;
        }
        $mydb->set_charset('utf8mb4');
        return $mydb;
    }

    // CLI test: when the file is run directly, keep original test behavior via testDB()
    if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
        $mydb = testDB();
        if (!$mydb) {
            // testDB() currently exits on failure; this is just a safety
            echo "Failed to connect to the database" . PHP_EOL;
            exit(1);
        }

        echo "Successfully connected to database" . PHP_EOL;
        $query = "SELECT id, emailAddress, uname FROM usersTable;";
        $response = $mydb->query($query);
        if (!$response) {
            echo "Failed to execute query: " . $mydb->error . PHP_EOL;
            $mydb->close();
            exit(1);
        }

        while ($row = $response->fetch_assoc()) {
            echo "ID: " . $row['id'] . " | Email: " . $row['emailAddress'] . " | Username: " . $row['uname'] . PHP_EOL;
        }
        $mydb->close();
    }

// CLI test: only run when executed directly
if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $mydb = get_db_connection();
    if (!$mydb) {
        echo "Failed to connect to the database" . PHP_EOL;
        exit(1);
    }

    echo "Successfully connected to database" . PHP_EOL;

    $query = "SELECT id, emailAddress, uname FROM usersTable;";
    $response = $mydb->query($query);
    if (!$response) {
        echo "Failed to execute query: " . $mydb->error . PHP_EOL;
        $mydb->close();
        exit(1);
    }

    while ($row = $response->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Email: " . $row['emailAddress'] . " | Username: " . $row['uname'] . PHP_EOL;
    }
    $mydb->close();
}