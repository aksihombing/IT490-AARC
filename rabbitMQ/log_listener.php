<?php
require_once __DIR__ . '/rabbitMQLib.inc'; //will probably need to fix path like usual

$server = new rabbitMQServer(__DIR__ . "/host.ini", "logListener"); // need .ini section for this
$queue_name = "logs.backend"; //change for each vm
function log_process($req){
    $log = json_encode($req);

    file_put_contents("/var/log/distributed.log", $log.PHP_EOL, FILE_APPEND); //https://www.php.net/manual/en/function.file-put-contents.php

    return ["status" => "received"];
}

$server->process_requests("log_process",$queue_name);

?>