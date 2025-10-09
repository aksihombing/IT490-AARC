<?php
// reads rmq connection and queue info from ini files
// goibg to be used by both webserver and db scripts

function getHostInfo(array $extra = NULL)
{
    // try to use an environment variable, optional
    $iniPath = getenv('INIPATH');

    // if variable not set, default to the current folder
    if (!$iniPath || !is_dir($iniPath)) {
        $iniPath = __DIR__; // __DIR__ is where this file lives (rabbitMQ/)
    }

    // path to the main host.ini file that contains connection info
    $hostFile = rtrim($iniPath, '/') . '/host.ini';

    // parse host.ini
    $machine = parse_ini_file($hostFile, true);
    if ($machine === false) {
        throw new RuntimeException("getHostInfo: cannot read $hostFile");
    }

    // merges in any extra .ini files 
    // idk if we need this because i put everything back in host.ini???
    if ($extra) {
        foreach ($extra as $ini) {
            $p = (strpos($ini, '/') === false)
                ? rtrim($iniPath, '/') . '/' . $ini
                : $ini;

            $parsed = parse_ini_file($p, true);
            if ($parsed === false) {
                throw new RuntimeException("getHostInfo: cannot read $p");
            }
            
            $machine = array_replace($machine, $parsed);
        }
    }

    // SHOULD return proper combined config array
    return $machine;
}
