<?php

/**
    https://www.php.net/manual/en/function.parse-ini-file.php
    https://confloader.readthedocs.io/en/stable/writing_ini.html
    https://www.php.net/manual/en/function.array-map.php

    copied and reused professor's get_host_info.inc

    @return parsed ini with bundle file paths
*/

function getPathInfo(string $section)
{

    $path = __DIR__ . "/bundlepaths.ini";
    if (!file_exists($path)) {
        die("bundle path ini not found at $path\n");
    }

    // parse section
    $parsePath = parse_ini_file($path, true);

    if(!$parsePath){
        die("Failed to parse path: $path\n");
    }

    // convert string to array of paths
    $filepaths = [];
    foreach ($filepaths[$section] as $key => $value){
        $filepaths[$key] = array_map('trim', explode(',' $value));
    }


    return $filepaths;
}