#!/usr/bin/php
<?php

// work in progress bc im stuck on it

// db config
$host = 'localhost';
$user = 'apiAdmin';
$pass = 'aarc490';
$name = 'apidb';

// api endpoint
$RECENT_API_URL  = "";
$POPULAR_API_URL = "";



try {
    $conn = new mysqli($host, $user, $pass, $name);
    if ($conn->connect_errno) {
        throw new RuntimeException("DB connect failed: " . $conn->connect_error);
    }
    

    // clear existing data
    
    
    


}