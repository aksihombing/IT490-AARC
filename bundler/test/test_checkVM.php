#!/usr/bin/php
<?php

$checkIP = trim(shell_exec("hostname -I | awk '{print $1}'"));
$whichVM = [
    '172.28.108.126' => 'Frontend',
    '172.28.219.213' => 'Backend',
    '172.28.109.126' => 'DMZ'
];
$section = null;

foreach ($whichVM as $ip => $vmName){
    $shellcmd = "hostname -I | grep $ip";
    exec($shellcmd, $output, $returnCode);

    if ($returnCode === 0){
        $section = $vmName;
        break;
    }
}

if ($section === null){
    echo "Could not determine VM. IP Address not expected.\n";
}
else{
    echo "Running on VM section: $section\n";
}

?>