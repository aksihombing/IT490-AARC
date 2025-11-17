#!/usr/bin/php
<?php



ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);

// rmq script to stay running and listen for messages. db listener
// when it receives a message, should talk to sql and send back a result


///REWRITE EXPLANATION COMMENTS 

require_once('rabbitMQLib.inc');
require_once('get_host_info.inc');
require_once('db_config.inc.php');
//require_once('clusters.ini');

// sends version back to the php script used for the bundle on development cluster
function doVersionRequest(array $req)
{// looks for the next  version number of a bundle
  echo "Processing 'version_request'\n";// replace with with type from Rea's bundler script
  $db = db();
  if ($db === null) {
    return ['status' => 'fail', 'message' => 'db connection error'];
  }

  $bundle_name = $req['bundle_name'] ?? '';// reads the bundle name 
  if (empty($bundle_name)) {
    return ['status' => 'fail', 'message' => 'missing bundle_name'];
  }

  $stmt = $db->prepare("SELECT MAX(version) as max_v FROM bundles WHERE bundle_name = ?");
  $stmt->bind_param('s', $bundle_name);// selects the highest version number for the specific bundle
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();

  $max_version = $result['max_v'] ?? 0 + 1; // it takes the highest version it found (0 if it found none)  and adds 1 to it and that will become the new version number
  $stmt->close();
  $db->close();
  return ['status' => 'success', 'version' => $max_version]; // sends it back to the bundle php script
}

// the dev cluster's bundle php will need to send a request TO this function
function doAddBundle(array $req)
{ // adter the bundle script gets the new version number and the .zip/.tar or whichever 
  // is uploaded to the bundles directory on the dep vm , it will call this function to add the bundle info to the db
  echo "Processing 'add_bundle'\n";// replace with type from rea's bundler script
  $db = db();
  if ($db === null) {
    return ['status' => 'fail', 'message' => 'db connection error'];
  }



  $bundle_name = $req['bundle_name'] ?? '';
  $version = (int) ($req['version'] ?? 0);
  

  if (empty($bundle_name) || $version <= 0) {
    return ['status' => 'fail', 'message' => 'missing bundle_name'];
  }

  $stmt = $db->prepare("INSERT INTO bundles (bundle_name, version) VALUES (?,?)");// creates a new row for the bundle and this bundle is given a status of new by default
  $stmt->bind_param('si', $bundle_name, $version);

  if ($stmt->execute()) {
    $stmt->close();
    $db->close();
    return [
      'status' => 'success',
      'message' => 'Bundle added'
    ];
  } else {
    $error = $stmt->error;
    $stmt->close();
    $db->close();
    return ['status' => 'fail', 'message' => 'DB insert error: ' . $error];

  }
}


// this is updated FROM THE QA layer to update the status of a bundle
function doStatusUpdate(array $req)
{ //after the installer scripts is done installing and testing a bundle, it will send a message with the result passed or failed.
  echo "Processing 'status_update'\n";// replace with type from Aida's installer script
  $db = db();
  if ($db === null) {
    return ['status' => 'fail', 'message' => 'db connection error'];
  }

  $bundle_name = $req['bundle_name'] ?? '';
  $version = (int) ($req['version'] ?? 0);
  $status = $req['status'] ?? '';

  if (empty($bundle_name) || $version <= 0 || !in_array($status, ['passed', 'failed'])) {
    return ['status' => 'fail', 'message' => 'missing bundle_name'];
  }

  $stmt = $db->prepare("UPDATE bundles SET status = ? WHERE bundle_name = ? AND version = ?");// this records the result of the installation test by updatinf the fields in the db
  $stmt->bind_param('ssi', $status, $bundle_name, $version);
  $stmt->execute();



  $stmt->close();
  $db->close();
  doDeployBundle([
    'bundle_status' => $status,
    'bundle_name' => $bundle_name
  ]);
  return ['status' => 'success', 'message' => 'Status updated'];
}



function doDeployBundle(array $deployInfo) // base made by Rea
{
  // decides if it needs to be sent to QA or production. needs to be called after a status was updated or bundle was 
  // is run after doStatus
  $bundle_status = $deployInfo['bundle_status'];
  $bundle_name = $deployInfo['bundle_name'];
  $destination_cluster = null;
  $destination_vm = null;

  //need a map to route bundles based on where they are going frontend/backend/dmz

  
  switch ($bundle_status) { // VERIFY IF UPPERCASE OR LOWERCASE
    case 'new':
      $destination_cluster = 'QA';
      break;
    case 'passed':
      $destination_cluster = 'Production';
      break;
    case 'failed':
      doRollback([
        'destination_cluster' => $destination_cluster, 
          // NEED TO FIX; how do we differentiate between a failure in QA vs Prod?
        'destination_vm' => $destination_vm,
        'bundle_name' => $bundle_name
      ]);
      break;
    default:
      echo "Unable to resolve Deploy Status '{$bundle_status}'\n";
      return ['status' => 'fail', 'message' => 'Unable to resolve Deploy Status ' . $bundle_status . '\n'];
  }




  switch ($destination_cluster) { // check destination for where to send bundle to
    case 'QA':
      // call
      break;
    case 'Production':
      // call
      break;
    default:
      echo "Unable to resolve Destination Cluster '{$destination_cluster}'\n";
      return ['status' => 'fail', 'message' => 'Unable to resolve Destination Cluster ' . $destination_cluster . '\n'];
  }



  if ($bundle_name){ // need to parse through cluster.ini to select where it should go BASED ON THE NAME OF THE BUNDLE !

  }


}

function sendBundle(array $deployInfo)
{ // helper function to prevent using a nested switch in doDeployBundle

  


}

function doRollback(array $rollbackReq)
{ // helper function to do a rollback
  // array ['destination_cluster', 'destination_vm', 'bundle_name']
  $destination_cluster = $rollbackReq['destination_cluster'];
  $destination_vm = $rollbackReq['destination_vm'];
  $bundle_name = $rollbackReq['bundle_name'];


  // search in the database for the highest version of the given bundle_name

  // scp into destination

  // should also call sendBundle so that it re-sends and re-installs rolled-back bundle
}


/*function idk yet(){
  // may need more functions
  // possibly for the roll back handler later where the script ask for the last passed version of a bundle, and then have a queue to send that back
  // and then find a way to return that file ???
}
*/



//  REQUEST PROCESSOR : same logic from the rabbitmq server 

function requestProcessor(array $req)
{
  echo "Received request:\n";
  var_dump($req);
  flush();

  if (!isset($req['type'])) {
    return ['status' => 'fail', 'message' => 'no type'];
  }

  switch ($req['type']) {
    case 'version_request':// from Rea's bundler script
      return doVersionRequest($req);
    case 'add_bundle':// from Rea's bundler script
      return doAddBundle($req);
    case 'status_update'://from Aida's installer script
      return doStatusUpdate($req);


    default:
      return ['status' => 'fail', 'message' => 'unknown type'];
  }
}

echo "Auth server ready, waiting for requests\n";
flush();

// multi-queue capable version of the queue

// uses pcntl_fork -->  https://www.php.net/manual/en/function.pcntl-fork.php
$which = $argv[1] ?? 'all';
$iniPath = __DIR__ . "/host.ini";

if ($which === 'all') { // to run all queues for DB and RMQ connection
  echo "Deployment server starting for all queues...\n";
  $sections = ['deployVersion', 'deployStatus'];

  foreach ($sections as $section) {
    $pid = pcntl_fork(); // process control fork; creats child process 
    if ($pid == -1) {
      die("Failed to fork for {$section}\n");
    } elseif ($pid === 0) {
      // child process
      echo "Listening on {$section}\n";
      $server = new rabbitMQServer($iniPath, $section);
      $server->process_requests('requestProcessor');
      exit(0);
    }
  }

  while (pcntl_wait($status) > 0) {
  }
} else {
  echo "Deployment server starting: {$which}\n";
  $server = new rabbitMQServer($iniPath, $which);
  echo "Connecting to queue: {$which}\n";
  flush();
  $server->process_requests('requestProcessor');
  echo "Deployment sever stopped for {$which}\n";
}