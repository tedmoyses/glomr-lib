<?php

/**
 * This is a VERY SHADY way to get a file created with a short delay
 * it will fail if called from anywhere but our project root
 * it will fail if directory doesn't exist i.e. not during a test run
 * It probably should validate the path somehow
 */


$file = $argv[1];
usleep($argv[2]);
touch(getcwd(). "/".  $file);
unlink($file);
