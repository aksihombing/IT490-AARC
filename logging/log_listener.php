<?
require_once __DIR__ . '/rabbitMQLib.inc'; //will probably need to fix path like usual

$server = new rabbitMQServer("host.ini", "logFanout"); // i think ill need to change this or the other one

$server->consume("queue"){//logic here still cant figure out
    $data = json_encode($log);
    file_put_contents("/var/log/distributed.log", $log.PHP_EOL, FILE_APPEND); //https://www.php.net/manual/en/function.file-put-contents.php
}

?>