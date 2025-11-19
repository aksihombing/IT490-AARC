#!/usr/bin/php
<?php
require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/get_host_info.inc';

// https://www.php.net/manual/en/function.shell-exec.php
// https://www.php.net/manual/en/function.exec.php

// HELPER FUNCTIONS -------------------------
function getBundleInfo(string $section, string $bundle_name, string $bundle_attribute)
{
    $path = __DIR__ . "/bundleconfig.json";
    if (!file_exists($path)) {
        echo "bundle path ini not found at $path\n";
        exit(1);
    }

    // parse section (changed it from the ini parse to json parse bc idk how to parse ini sorry)
    $path_decoded = json_decode(file_get_contents($path), true);

    if (!$path_decoded) {
        echo "Failed to parse path : $path\n";
        exit(1);
    }

    // check if bundle_name found:
    if (!isset($path_decoded[$section][$bundle_name])) {
        echo "Bundle Name '$bundle_name' not found at $path\n\n";
        echo "Please select appropriate bundle name:\n";
        echo "Frontend: userFeatures , clubFeatures , bookFeatures, baseFeatures\n
                Backend: userData, bookData, databaseProcess\n
                DMZ: apiProcess\n";

        //$debuggingarray = implode( ", ", $path_decoded[$section][$bundle_name]);
        //echo "Checking if bundle_name is found: "; //DEBUGGING
        //print_r($path_decoded[$section][$bundle_name]); //DEBUGGING
        //echo "\n"; //DEBUGGING
        exit(1);
    }

    // not sure if necessary, but checks if the bundle has a PATHS or COMMAND specified
    if (!isset($path_decoded[$section][$bundle_name][$bundle_attribute])) {
        echo "Script error: '$bundle_name' missing path/command in json file. Was given $bundle_attribute\n\n";
        exit(1);
    }

    $parsed_json = $path_decoded[$section][$bundle_name][$bundle_attribute];
    //echo "PATHS_FROM_JSON IS : "; //DEBUGGING
    //print_r($paths_from_json); //DEBUGGING
    //echo "\n"; //DEBUGGING

    // convert to string ??? with implode
    // escapeshellarg translates it to be able to work as a shell argument, which is good for when its actually called
    // https://www.php.net/manual/en/function.escapeshellarg.php maybe?
    $attribute_list = null;
    if ($bundle_attribute == 'paths') {
        $attribute_list = implode(' ', array_map('escapeshellarg', $parsed_json));
    } elseif ($bundle_attribute == 'commands') {
        $attribute_list = implode("\n\n", $parsed_json);
    }

    //echo "PATHS_LIST IS : $attribute_list\n"; //DEBUGGING

    // php.net/manual/en/function.realpath.php ?? not sure if needed, but a reference in case i need it later
    //$full_paths = [];
    /*foreach ($paths_from_json as $relative_path){
        $full_paths[] = __DIR__ . $relative_path;
    }*/
    //return $full_paths;
    //return $paths_from_json;


    return $attribute_list;
}





// ACTUAL BUNDLING SCRIPT ---------------------------
// we dont need the shell script bc argv already accepts the name of the bundle, silly me :P
$bundle_name = $argv[1];
$version = null;
$section = null;
$projectRootPath = realpath(__DIR__ . "/..");
// php.net/manual/en/function.realpath.php --> need to cd into the project root before i tar the files to maintain holder hierarchy and bc bundler/ is adjacent to all other dev folders


// CHECK WHICH VM THIS BUNDLE SCRIPT IS ON 
// this could definitely be done if we set etc/hosts with the correct ip addresses
// example : 172.28.109.126 dev-dmz
$checkIP = trim(shell_exec("hostname -I | awk '{print $1}'"));
$whichVM = [
    '172.28.108.126' => 'Frontend',
    '172.28.219.213' => 'Backend',
    '172.28.109.126' => 'DMZ'
];

// find which vm the bundlr script is being run on
foreach ($whichVM as $ip => $vmName) {
    $shellcmd = "hostname -I | grep $ip";
    // note to self: we could use hostname more effectively if we assigned each ip in /etc/hosts
    exec($shellcmd, $output, $returnCode);
    // similar to shell_exec() but saves output and returncode

    // iterate thru each possible ip for the dev layer
    if ($returnCode === 0) {
        $section = $vmName;
        break;
    }
}

if ($section === null) {
    echo "Could not determine VM. IP Address not expected.\n";
} else {
    echo "Running on VM section: $section\n";
}



// CHECK DEPLOY VERSION ---------- calls deploy vms checkVersion
// get argument to know the bundle name

// the deployment listener will be the one to update the name of the bundle by checking its own database
try {
    $client = new rabbitMQClient(__DIR__ . '/deployQueues.ini', 'DeployVersion');
    // need to verify WHERE the bundle script itself will live + make sure host.ini includes the new queue + host specific to the deployment vm

    $request = [
        'type' => 'version_request',
        'bundle_name' => $bundle_name
    ];
    // build + send request
    $response = $client->send_request($request);

    /*if (isset($response['status']) && $response['status'] === 'success') {
        $version = $response['version'];
        echo "Successfully received response from remote\n";
    }
    else{echo "Error: Bundle not sent\n";}
    $client->close();*/
    if (!isset($response['status']) || $response['status'] === 'fail') {
        echo "Unable to get version number from database\n";
        exit(1);
    }
    // previous_version + 1
    if ($version !== 1) {
        $version = $response['version'] + 1;
    } else {
        $version = $response['version'];
    }
    //echo "Section: $section || Bundle Name: $bundle_name\n"; //DEBUGGING
    echo "Successfully received response from remote. $bundle_name version number is $version.\n";



} catch (Exception $e) {
    echo "Failure to send bundle to deployment listener script: " . ($e->getMessage());
    exit(1);
}

// CREATE BUNDLE
// get file paths for the section and bundle_name
$tar_name = "$version" . "_" . "$bundle_name" . ".tar.gz";
$file_path = getBundleInfo($section, $bundle_name, "paths");
$tar_path = "$projectRootPath/bundles/$tar_name";

//$parent_path = dirname($projectRootPath); // im losing my mind trying to get tar working from the parent directory.....
//echo "projectRootPath: $projectRootPath || parent_path: $parent_path\n"; //DEBUGGIN
// php.net/manual/en/function.dirname.php 

// need to go one file back to run the tar + create a folder to place the tar on the local machine too
shell_exec("cd $projectRootPath && mkdir -p bundles/"); // MAKE DIR IF NOT EXISTS !!!



// TO DO: [CREATE CONFIG FILE AND ADD IT INTO THE TAR]
// https://www.geeksforgeeks.org/php/php-file_put_contents-function/
$config_script = getBundleInfo($section, $bundle_name, "commands");
$config_path = $projectRootPath . "/configure.sh";
file_put_contents($config_path, "#!/bin/bash\n\n" . $config_script);
chmod($config_path, 0755); // wxr for owner + others




exec("cd $projectRootPath && tar -czf $tar_path  configure.sh $file_path", $tar_output, $tar_returnCode);
if ($tar_returnCode !== 0) {
    echo "Error: Unable to bundle $tar_name\n";
    exit(1);
}

// SEND BUNDLE
// scp to deployment
exec("scp '$tar_path' chizorom@172.28.121.220:/var/www/bundles/", $scp_output, $scp_returnCode); // update name of user later !!
if ($scp_returnCode !== 0) {
    echo "Error: Unable to scp $tar_path to deployment\n";
    exit(1);
} else {
    echo "Successfully send $tar_path to deployment\n";
}


// CALL AddDeploy ----------------
// TELL DEPLOYMENT TO ADD THE BUNDLE TO THE DATABASE TOO!
try {
    $client = new rabbitMQClient(__DIR__ . '/deployQueues.ini', 'DeployVersion'); // need to verify WHERE the bundle script itself will live + make sure host.ini includes the new queue + host specific to the deployment vm

    $request = [
        'type' => 'add_bundle',
        'bundle_name' => $bundle_name,
        'version' => $version
    ];
    // build + send request
    $response = $client->send_request($request);

    if (!isset($response['status']) || $response['status'] === 'fail') {
        echo "Unable to get version number from database\n";
        exit(1);
    }
    echo "Successfully sent request to Deploy VM to update database\n"; // im assuming that it is a success


} catch (Exception $e) {
    echo "Failure to send bundle to deployment listener script: " . ($e->getMessage());
    exit(1);
}

// delete configure.sh after it was created to prevent overlapped
shell_exec("sudo rm configure.sh");

// to tell bundle.sh that it was successful : 
exit(0);
?>
