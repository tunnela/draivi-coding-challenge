<?php

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

$env = parse_ini_file($root . '/.env');

foreach ($env as $key => $value) {
    putenv("$key=$value");
}