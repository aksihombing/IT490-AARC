<?php

/**
    @brief gets the currrent machine information and optionally any other task
    specific INI folder installed in the system path.
    Uses the default path of /var/system_ini/ unless $INIPATH is set

    @return parsed ini machine information
*/

function getHostInfo(array $extra = NULL)
{

    $path = __DIR__ . "/host.ini";
    if (!file_exists($path)) {
        die("host ini not found at $path\n");
    }

    $machine = parse_ini_file(__DIR__ . "/host.ini", true);

    if ($extra != NULL)
    {
        foreach ($extra as $ini)
        {
            if (is_string($ini) && isset($machine[$ini])) {
                continue;
            }

            if (file_exists($ini)) {
                $parsed = parse_ini_file($ini, true);
                if ($parsed) {
                    $machine = array_merge($machine, $parsed);
                }
            }
        }
    }
    return $machine;
}

function getClusterInfo($ip) {
    $clusterPath = __DIR__ . "/clusters.ini";
    if (!file_exists($clusterPath)) return null;
    $cluster_conf = parse_ini_file($clusterPath, true);

    $ip_find = trim($ip, '" ');

    foreach (['QA','Prod'] as $cluster) {
        if (in_array($ip_find, $cluster_conf[$cluster])){
            return $cluster;
        }
    }
//loops through clusters QA and Prod, checks if ip exists, match found = cluster name returned

    return null;
}


function getVmIp($bundle_name, $cluster) {
    $clusterPath = __DIR__ . "/clusters.ini";
    if (!file_exists($clusterPath)) return null;
    $clusterI = parse_ini_file($clusterPath, true);

    $vm_name = $clusterI['BundleDestinations'][$bundle_name] ?? null;
    if ($vm_name === null) return null;

    $vm_ip = $clusterI[$cluster][$vm_name] ?? null;
    if ($vm_ip === null || empty(trim($vm_ip, '" '))) return null;

    return trim($vm_ip, '" ');
}// gets the vm ip based on the bundle name and cluster given
//looks for key like frontend, backend, or dmz dependending on cluster name



?>
