<?php
require_once __DIR__ . '/log_producer.php';

log_event("frontend", "info", "test log from frontend");

echo "log sent\n";
?>
