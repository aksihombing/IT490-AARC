#!/usr/bin/php
<?php
require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/get_host_info.inc';

// https://www.php.net/manual/en/function.shell-exec.php
// https://www.php.net/manual/en/function.exec.php

// HELPER FUNCTIONS -------------------------
function getPathInfo (string $section, string $bundle_name){
    $path = __DIR__ . "/bundlepaths.json";
    if (!file_exists($path)) {
        die("bundle path ini not found at $path\n");
    }

    // parse section (changed it from the ini parse to json parse bc idk how to parse ini sorry)
    $path_decoded = json_decode(file_get_contents($path, true));

    if(!$path_decoded){
        die("Failed to parse path : $path\n");
    }

    // check if bundle_name found:
    if(!isset($path_decoded[$section][$bundle_name])){
        die("Bundle Name '$bundle_name' not found at $path\n");
    }

    $paths_from_json = $path_decoded[$section][$bundle_name];

    // convert to string ??? with implode
    // https://www.php.net/manual/en/function.escapeshellarg.php maybe?


    // php.net/manual/en/function.realpath.php ?? not sure if needed, but a reference in case i need it later
    
    //$full_paths = [];
    /*foreach ($paths_from_json as $relative_path){
        $full_paths[] = __DIR__ . $relative_path;
    }*/

    //return $full_paths;

    return $paths_from_json;
}





// ACTUAL BUNDLING SCRIPT ---------------------------
// CHECK WHICH VM THIS BUNDLE SCRIPT IS ON 
// this could definitely be done if we set etc/hosts with the correct ip addresses
// example : 172.28.109.126 dev-dmz
$checkIP = trim(shell_exec("hostname -I | awk '{print $1}'"));
$whichVM = [
    '172.28.108.126' => 'Frontend',
    '172.28.219.213' => 'Backend',
    '172.28.109.126' => 'DMZ'
];
$section = null;
$projectRootPath = realpath(__DIR__ . "/..");
// php.net/manual/en/function.realpath.php --> need to cd into the project root before i tar the files to maintain holder hierarchy and bc bundler/ is adjacent to all other dev folders

// find which vm the bundlr script is being run on
foreach ($whichVM as $ip => $vmName){
    $shellcmd = "hostname -I | grep $ip"; 
    // note to self: we could use hostname more effectively if we assigned each ip in /etc/hosts
    exec($shellcmd, $output, $returnCode); 
    // similar to shell_exec() but saves output and returncode

    // iterate thru each possible ip for the dev layer
    if ($returnCode === 0){
        $section = $vmName;
        break;
    }
}

if ($section === null){
    echo "Could not determine VM. IP Address not expected.\n";
}
else{
    echo "Running on VM section: $section\n";
}



// CHECK DEPLOY VERSION ---------- calls deploy vms checkVersion
// get argument to know the bundle name
$bundle_name = $argv[1];
$version = null;
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

    if (isset($response['status']) && $response['status'] === 'success') {
        $version = $response['version'];
        echo "Successfully received response from remote\n";
    }
    else{echo "Error: Bundle not sent\n";}
    $client->close();
    

} catch (Exception $e) {
    $client->close();
    echo "Failure to send bundle to deployment listener script: " . ($e->getMessage());
}

// CREATE BUNDLE
// get file paths for the section and bundle_name
$tar_name = "$version" . "_" . "$bundle_name" . ".tar.gz";
$file_path = getPathInfo($section, $bundle_name);
$tar_path = "$projectRootPath/bundles/$tar_name";
// fileList = implode(' ', array_map('escapeshellarg', $file_path);
// need to go one file back to run the tar + create a folder to place the tar on the local machine too
exec("cd $projectRootPath && tar -czf $tar_path $file_path .", $tar_output, $tar_returnCode);
if ($tar_returnCode !== 0) {
    echo "Error: Unable to bundle $tar_name\n";
}

// SEND BUNDLE
// scp to deployment
exec("scp '$tar_path' chizorom@172.28.121.220:/var/www/bundles/", $scp_output, $scp_returnCode);
if ($scp_returnCode !== 0) {
    echo "Error: Unable to scp $tar_path to deployment\n";
}
else {echo "Successfully send $tar_path to deployment\n";}


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

    if (isset($response['status']) && $response['status'] === 'success') {
        echo "Successfully sent request to Deploy VM to update database\n";
    }
    else {echo "Error: Deploy VM was unable to update database\n";}
    $client->close();
    

} catch (Exception $e) {
    $client->close();
    echo "Failure to send bundle to deployment listener script: " . ($e->getMessage());
}

// to tell bundle.sh that it was successful : 
exit(0);
?>