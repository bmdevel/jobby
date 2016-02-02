<?php

use Jobby\BackgroundJob;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../../../autoload.php';
}

require "BackgroundJob.php";

global $argv;
parse_str($argv[2], $config);
$job = new BackgroundJob($argv[1], $config);
$job->run();