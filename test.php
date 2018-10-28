<?php

require('vendor/autoload.php');
//use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Symfony\Component\Finder\Finder;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

//$finder = new Finder();
/*foreach($finder->directories()->in('.') as $dir){
  var_dump($dir);
}*/

//var_dump(preg_grep('/.*\.php$/i', Filesystem::allFiles('src')));
$adapter = new Local(getcwd());
$fs = new Filesystem($adapter);

var_dump(array_filter($adapter->listContents('/'), function($path){if ($path['type'] === 'file') return true;}));
