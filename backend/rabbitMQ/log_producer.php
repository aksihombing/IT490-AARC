<?php
require_once __DIR__ . '/rabbitMQLib.inc'; //will probably need to fix path like usual
function log_event($vm, $type, $log){
    $client = new rabbitMQClient(__DIR__ . "/host.ini", "logProducer"); 

    $log_map = [
        'timestamp' => date("D M j G:i:s"), //timestamp formatted  Sat Mar 10 17:16:18 https://www.php.net/manual/en/function.date.php
        'vm' => $vm, // frontend, backend, dmz
        'type' => $type, //error, warning, etc
        'log' => $log,
    ];

    $client->publish($log_map);

    return true;
}

/*
use this logic wherever we need to log errors? am i understanding this correctly

if (!$variable/condition/etc){
    log_event("vm_name","type","log")
}

*/

?>