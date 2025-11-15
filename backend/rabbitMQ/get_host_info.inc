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
?>
