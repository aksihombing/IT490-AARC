<?php 

function db(){
    $host = 'localhost';
    $user = 'aarc';
    $pass = 'aarc490';
    $name = 'deploydb';

    $mysqli = new mysqli($host, $user, $pass, $name);

    if ($mysqli->connect_errno) {
        error_log("deploydb connection failed: " . $mysqli->connect_error);
        
        return null;
        
    }
    return $mysqli;
}
?>