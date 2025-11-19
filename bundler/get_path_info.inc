<?php

/**
    https://www.php.net/manual/en/function.parse-ini-file.php
    https://confloader.readthedocs.io/en/stable/writing_ini.html
    https://www.php.net/manual/en/function.array-map.php

    copied and reused professor's get_host_info.inc

    @return parsed ini with bundle file paths
*/

function getPathInfo(string $section, string $bundle_name)
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


    // check if section found:
    if(!isset($parsePath[$section])){
        die("Section '$section' not found at $path\n");
    }

    // check if bundle_name found:
    if(!isset($parsePath[$section][$bundle_name])){
        die("Bundle Name '$bundle_name' not found at $path\n");
    }

    $paths = $parsePath[$section][$bundle_name];

    return $paths;
}