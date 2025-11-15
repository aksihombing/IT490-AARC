#!/usr/bin/php
<?php

$checkIP = trim(shell_exec("hostname -I | awk '{print $1}'"));
$whichVM = [
    '172.28.108.126' => 'Frontend',
    '172.28.219.213' => 'Backend',
    '172.28.109.126' => 'DMZ'
];
$section = $whichVM[$ip] ?? null;
echo "Running on VM section: $section";

?>