<?php
require_once __DIR__ . '/log_producer.php';

log_event("backend", "info", "test log from backend");

echo "log sent\n";
?>
