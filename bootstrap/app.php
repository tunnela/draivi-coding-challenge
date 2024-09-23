<?php

$rootPath = dirname(__DIR__);

require $rootPath . '/vendor/autoload.php';

$env = parse_ini_file($rootPath . '/.env');

foreach ($env as $key => $value) {
    putenv("$key=$value");
}

function root_path($path = '/') 
{
    global $rootPath;

    return str_replace(
        ['\\', '/'], 
        DIRECTORY_SEPARATOR, 
        $rootPath . DIRECTORY_SEPARATOR . trim($path, '\\/')
    );
}