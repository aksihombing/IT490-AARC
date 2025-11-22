<?php

// this script is to go on the backup VM only 
const primaryVM = "172.28.172.114";  // might need to be change bc it might be a differnt machine ip address? 
const backVM = ""; // i chose this bc im going to make it match with the backup vm 

const heartBeatURL = "http://172.28.172.114/healthCheck.php";  

// for the usewrs time 
const heartBeatTime = 4;
const heartBeatRetry = 3;

const timeCheck = 2; 
const timeFailture = 3;

const statusFile = "/";
// shell scripts / the file paths 
const db = 'sudo /user/local/bin/db.sh';
const dbTakeover = 'sudo /user/local/bin/dbTakeover.sh';

function checkCurrentIP(): string 
{
    
    if (!file_exists(STATE_FILE)) {
        logMessage("Status file is not real or missing or is the primary stuff" . primaryVM);
        return primaryVM;
    }
    
    $ip = trim(file_get_contents(STATE_FILE));
    
    return $ip ?: primaryVM;
}

function setCurrentIP (string $ip) :bool 
{
    $sucess = fileRefresh(statusFile, $ip); 
    if $sucess === false
    (
        logMessage ("something is wrong the file could not be written"); 
        return false;
    )
    logMessage ("the file could be written");
    return true;
}

function isPrimaryOkay(): bool 
{
    $curlStuff = curl_init(HEARTBEAT_URL); 
    curl_setopt($curlStuff, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($curlStuff, CURLOPT_TIMEOUT, timeCheck); 
    $curlExecute = curl_exec($curlStuff); 
    $httpCode = curl_getinfo($curlStuff, CURLINFO_HTTP_CODE);
    curl_close($curlStuff); 

    return $httpCode === 200;
}

</php>

