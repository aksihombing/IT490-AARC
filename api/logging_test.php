<?php
require_once __DIR__ . '/log_producer.php';

log_event("dmz", "info", "test log from dmz");

echo "log sent\n";
?>
