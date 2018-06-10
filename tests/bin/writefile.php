<?php

/**
 * This is a VERY SHADY way to get a file created in our source
 * it will fail if called from anywhere but our project root
 * it will fail if tests/source directory doesn't exist i.e. not udring a test run
 */
usleep(50000);
$file = $argv[1];
$content = $argv[2];
file_put_contents("./tests/source/$file", $content);
