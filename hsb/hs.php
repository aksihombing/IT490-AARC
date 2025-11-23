<?php

const primaryVM = "172.28.172.114";  
const backVM    = "";    // HAVE TO CHANE TO FIX/FIT THIS 
const heartBeatURL = "http://" . primaryVM . "/health_check.php";
const statusFile    = "/tmp/hsStatus"; 

// for the usewrs time 
const heartBeatTime = 4; 
const timeCheck     = 2; 
const timeFailture  = 3; 

// shell scripts / the file paths 
const db = 'sudo /usr/local/bin/db.sh';         
const dbTakeover = 'sudo /usr/local/bin/dbTakeover.sh'; 

function logMessage(string $message): void {
    echo date("Y-m-d H:i:s") . " --- " . $message . "\n";
}

function checkCurrentIP(): string {
    
    if (!file_exists(statusFile)) {
        logMessage("status file is missing so using primary " . primaryVM);
        return primaryVM;
    }
    
    $ip = trim(file_get_contents(statusFile));
    
    return $ip ?: primaryVM;
}

function setCurrentIP (string $ip) :bool {
    
    $success = file_put_contents(statusFile, $ip);

    if ($success === false) {
        logMessage("something is wrong the file could not be written"); 
        return false;
    }
    logMessage("the file could be written");
    return true;
}

function isPrimaryOkay(): bool {
    
    $curlStuff = curl_init(heartBeatURL); 
    curl_setopt($curlStuff, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($curlStuff, CURLOPT_TIMEOUT, timeCheck); 
    $curlExecute = curl_exec($curlStuff); 
    $httpCode = curl_getinfo($curlStuff, CURLINFO_HTTP_CODE);
    curl_close($curlStuff); 

    return $httpCode === 200;
}

function run_shell_command(string $command): void {
    
    logMessage("executing: $command");
    exec($command, $output, $returnVar);

    if ($returnVar !== 0) {
        logMessage("command failed (code $returnVar): " . implode("\n", $output));
    } else {
        logMessage("command successful");
    }
}

/* below is where the main function will be, 
// this functions is in charge of the curl, 
the shell will run and also the primary vms and ips will be checked or reverted etc 
*/
function mainStuff()
{
    $fails = 0;

    if (!extension_loaded('curl')) {
        logMessage("error: the curl extension is not loaded cannot perform heartbeat checks");
        return;
    }
    logMessage("hsb monitor started checking primary: " . primaryVM);

    while (true) {
        $currentActiveIP = checkCurrentIP();

        if ($currentActiveIP === primaryVM) {
            
            if (isPrimaryOkay()) {
                $fails = 0;
                logMessage("primary is ok fails reset");
            } else {
                $fails++;
                logMessage("primary failed check fails: $fails / " . timeFailture);

                if ($fails >= timeFailture) {
                    
                    logMessage("primary down starting the failover"); 
                    
                    run_shell_command(db); 

                    if (setCurrentIP(backVM)) {
                        logMessage("failover complete this is now active");
                    }
                    $fails = 0; 
                }
            }

        } elseif ($currentActiveIP === backVM) {
            
            if (isPrimaryOkay()) {
                
                logMessage("primary recovered starting failback"); 

                run_shell_command(dbTakeover); 
                
                logMessage("db reverted to replica waiting for primary to reclaim role");

            } else {
                logMessage("active and healthy (primary vm still down or not fully recovered)");
            }

        } else {
            
            logMessage("warning: status file is inconsistent resetting to default primary ip");
            setCurrentIP(primaryVM);
        }

        sleep(heartBeatTime);
    }
}

mainStuff();

?>