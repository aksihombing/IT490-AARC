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

const statusFile = "/tmp/hsStatus";
// shell scripts / the file paths 
const db = 'sudo /usr/local/bin/db.sh';
const dbTakeover = 'sudo /usr/local/bin/dbTakeover.sh';

function logMessage($msg) {
    echo date("Y-m-d H:i:s") . " — " . $msg . "\n";
}


function checkCurrentIP(): string 
{
    
    if (!file_exists(statusFile)) {
        logMessage("Status file is missing, so using primary " . primaryVM);
        return primaryVM;
    }
    
    $ip = trim(file_get_contents(statusFile));
    
    return $ip ?: primaryVM;
}

function setCurrentIP (string $ip) :bool 
{
    $success = file_put_contents(statusFile, $ip);
    if ($success === false) 
    {
    
        logMessage ("something is wrong the file could not be written"); 
        return false;
    }
    logMessage ("the file could be written");
    return true;
}

function isPrimaryOkay(): bool 
{
    $curlStuff = curl_init(heartBeatURL); 
    curl_setopt($curlStuff, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($curlStuff, CURLOPT_TIMEOUT, timeCheck); 
    $curlExecute = curl_exec($curlStuff); 
    $httpCode = curl_getinfo($curlStuff, CURLINFO_HTTP_CODE);
    curl_close($curlStuff); 

    return $httpCode === 200;
}

/* below is where the main function will be, 
// this functions is in charge of the curl, 
the shell will run and also the primary vms and ips will be checked or reverted etc 
*/
function mainStuff()
{
    $currentIP = checkCurrentIP();
    $fails = 0;

    while (true) {

        if (isPrimaryOkay()) {
            if ($currentIP !== primaryVM) {
                logMessage("real primary is back");
                setCurrentIP(primaryVM);
                shell_exec(db);
            }
            $fails = 0;
        } else {
            $fails++;

            if ($fails >= heartBeatRetry) {
                if ($currentIP !== backVM) {
                    logMessage("the primary is now reverted");
                    setCurrentIP(backVM);
                    shell_exec(dbTakeover);
                }
            }
        }

        sleep(heartBeatTime);
    }
}

mainStuff();
?>

