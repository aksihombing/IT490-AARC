<?
require_once __DIR__ . '/rabbitMQLib.inc'; //will probably need to fix path like usual
function log_event(){
    $server = new rabbitMQServer($iniPath, $which); // i think ill need to change this or the other one

    $log_map = [
        // figure out what i need here
    ]

    $server->publish($log_map, "logs.fanout")
}

/*
use this logic wherever we need to log errors? am i understanding this correctly

if (!$variable/condition/etc){
    log_event("vm_name",$error??,"message")
}

*/

?>