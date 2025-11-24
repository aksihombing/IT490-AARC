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

    $filename = $version . "_" . $bundle_name . ".tar.gz"; //do we include file extension too, .tar.gz or .zip?

    echo "Bundle {$filename} added to database, deploying to QA.\n";

    doDeployBundle([
      'bundle_status' => 'new',
      'bundle_name' => $bundle_name,
      'version' => $version,
      'path' => $filename,
      'cluster' => 'QA'
    ]);
    return [
      'status' => 'success',
      'message' => 'Bundle added to and sent over to QA'
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
  echo "Processing 'status_update'\n";

  $db = db();
  if ($db === null) {
    return ['status' => 'fail', 'message' => 'db connection error'];
  }

  $bundle_name = $req['bundle_name'] ?? '';
  $version = (int) ($req['version'] ?? 0);
  $status = $req['status'] ?? '';
  $sender_ip = $req['sender_ip'] ?? '';

  if (empty($bundle_name) || $version <= 0 || !in_array($status, ['passed', 'failed'])) {
    return ['status' => 'fail', 'message' => 'missing bundle_name'];
  }


  $cluster = getClusterInfo($sender_ip);
  if ($cluster === null) {
    return ['status' => 'fail', 'message' => '$sender_ip not found in clusters.ini'];
  }

  $stmt = $db->prepare("UPDATE bundles SET status = ? WHERE bundle_name = ? AND version = ?");// this records the result of the installation test by updatinf the fields in the db
  $stmt->bind_param('ssi', $status, $bundle_name, $version);
  $stmt->execute();


  $stmt->close();
  $db->close();

  $filename = $version . "_" . $bundle_name . ".tar.gz";// same question about the file extension

  doDeployBundle([
    'bundle_status' => $status,
    'bundle_name' => $bundle_name,
    'version' => $version,
    'path' => $filename,
    'cluster' => $cluster
  ]);
  return ['status' => 'success', 'message' => 'Status updated'];
}



function doDeployBundle(array $deployInfo) // base made by Rea
{
  // decides if it needs to be sent to QA or production. needs to be called after a status was updated or bundle was 
  // is run after doStatus
  $bundle_status = $deployInfo['bundle_status'];
  $bundle_name = $deployInfo['bundle_name'];
  $version = $deployInfo['version'];
  $destination_cluster = null;
  $destination_vm = null;
  $path = $deployInfo['path'];// not really the path but just the filename, will change soon
  $starting_cluster = $deployInfo['cluster']; //Where did it happen (QA or Prod)

  //need a map to route bundles based on where they are going frontend/backend/dmz


  switch ($bundle_status) { // VERIFY IF UPPERCASE OR LOWERCASE
    case 'new':
      $destination_cluster = 'QA';// new bundles always go to qa first
      break;
    case 'passed':
      if ($starting_cluster === 'QA') {// passed the test (after statusupdate from QA)
        $destination_cluster = 'Prod';
      } else {
        echo "Bundle $bundle_name v$version pased on to Production. Deplotment done.\n";
        return;
      }
      break;
    case 'failed':// if failed in prod do rollback, if it fails in qa it will just stop
      if ($starting_cluster === 'Prod') {
        echo "Bundle $bundle_name v$version failed in Production. Rolling back.\n";
        doRollback([
          'bundle_name' => $bundle_name
        ]);
      } else {
        echo "Bundle $bundle_name v$version failed in QA. Deployment stopping.\n";
      }
      return;
    default:
      echo "error: having trouble processing the status'\n";
      return;
  }

  echo "Deploying bundle $bundle_name v$version to $destination_cluster\n";
  // figuring out which vm to send it to baased on the bundle name and destination cluster

  //using the clusters ini to connect bundle names to vm names
  $clusters_ini = parse_ini_file(__DIR__ . "/clusters.ini", true);
  $vm_name = $clusters_ini['BundleDestinations'][$bundle_name] ?? null;

  //
  if (!$vm_name) {
    echo "Error: Uknown bundle name for '$bundle_name'\n";
    echo "VM Name is : $vm_name\n";
    return;
  }
  // getting the vm ip from the bundle name and destination cluster
//using the get host functions and the queue name is constructing the message to be sent, so that it knows which queue to go to based on the cluster and vm name
  $queue_name = "deploy" . $destination_cluster . $vm_name;
  $vm_ip = getVmIp($bundle_name, $destination_cluster);
  if ($vm_ip === null) {
    echo "Error: Unable to find VM IP for bundle '$bundle_name' in cluster '$destination_cluster'\n";
    return;
  }


  sendBundle([
    'queue_name' => $queue_name,
    'destination_cluster' => $destination_cluster,
    'vm_ip' => $vm_ip,
    'path' => $path,
    'bundle_name' => $bundle_name,
    'version' => $version
  ]);
}











function sendBundle(array $deployInfo)
{ // helper function to prevent using a nested switch in doDeployBundle

  echo "Install for " . $deployInfo['bundle_name'] . "\n";
  $iniPath = __DIR__ . "/host.ini";
  $filePath = $deployInfo['path'];
  $destinationIP = $deployInfo['vm_ip'];
  $destination_cluster = $deployInfo['destination_cluster'];
  $destination_user = strtolower("aarc-$destination_cluster");
  $client = new rabbitMQClient($iniPath, $deployInfo['queue_name']);


  /*
    shell_exec("sudo sshpass -p 'aarc' scp /var/www/bundles/$filePath $destination_user@$destinationIP:/var/www/bundles/"); 
  */
  exec("scp /var/www/bundles/$filePath $destination_user@$destinationIP:/var/www/bundles/"); // URGENT : NEED TO CHANGE LATER !!!!

  $request = [
    'type' => 'install_bundle',
    'path' => $deployInfo['path'],
    'bundle_name' => $deployInfo['bundle_name'],
    'version' => $deployInfo['version'],
    'vm_ip' => $deployInfo['vm_ip']
  ];

  $client->send_request($request);
  echo "Install sent\n";




}


function doRollback(array $rollbackReq)
{ // helper function to do a rollback
  // array ['destination_cluster', 'destination_vm', 'bundle_name']
  //$destination_cluster = $rollbackReq['destination_cluster'];

  //$destination_vm = $rollbackReq['destination_vm'];
  $bundle_name = $rollbackReq['bundle_name'];

  echo "Rolling back bundle: " . $rollbackReq['bundle_name'] . "\n";


  $db = db();
  if ($db === null)
    return;
  $stmt = $db->prepare("SELECT version FROM bundles WHERE bundle_name = ? AND status = 'passed' ORDER BY version DESC LIMIT 1");
  // finds the last good version following the query requirements 
  $stmt->bind_param('s', $bundle_name);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  $db->close();

  if (!$result) {
    echo "No previous version found to roll back to for bundle: $bundle_name\n";
    return;
  }// should really just show up if the first version failed, hopefully

  $old_version = $result['version'];

  // file name construction to be sent

  $old_path = $old_version . "_" . $bundle_name . ".tar.gz";// same question about file extension

  echo "Deploying rollback of $bundle_name to version $old_version\n";

  // will just call dodeploybundle with the old version infor to trigger the deployment process to Production but with the old version
  doDeployBundle([
    'bundle_status' => 'rollback',
    'bundle_name' => $bundle_name,
    'version' => $old_version,
    'path' => $old_path,
    'cluster' => 'Prod'
  ]);
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
  echo "--------------------------\n";
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
  $sections = ['deployVersion', 'deployStatus', 'deployQAfrontend', 'deployQAbackend', 'deployQAdmz', 'deployProdfrontend', 'deployProdbackend', 'deployProddmz'];

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