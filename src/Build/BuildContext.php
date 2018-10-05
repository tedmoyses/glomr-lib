<?php
namespace Glomr\Build;

use Glomr\Watch\InotifyEventsWatcher;
use Glomr\Watch\PollWatcher;
use Glomr\Log\Logr;

class BuildContext {
  private $paths = [
    'build' => './build',
    'source' => './src',
    'cache' => './cache',
    'assetJs' => 'assets/js',
    'assetCss' => 'assets/css',
    'assetImages' => 'assets/images'
  ];

  private $environment = 'dev';

  public function setPath(string $key, string $value){
    $this->paths[$key] = $value;
  }

  public function getPath($path){
    return $this->paths[$path];
  }

  public function setEnv($env){
    if(in_array($env, ['production', 'dev'])) $this->environment = $env;
  }

  public function getEnv(){
    return $this->environment;
  }

  public function fetchSourceDirectories() {
    $dirs = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator(
        $this->paths['source'],
        \RecursiveDirectoryIterator::SKIP_DOTS
      ),
      \RecursiveIteratorIterator::SELF_FIRST);
    $return = [];
    foreach($dirs as $dir){
      //if(is_dir($dir)) $return[] = $dir->getFilename();
      if(is_dir($dir)) $return[] = $dir->__toString();
    }
    return $return;
  }

  public function fetchSourceFiles(string $context = "", $regex = "/^.+$/i") {
    $path = $context == "" ? $this->paths['source'] : $this->paths['source'] . DIRECTORY_SEPARATOR . $context;
    if (!file_exists($path) && !is_dir($path)){
      throw new \Exception("File path not found ${path}", 1);
    }

    $directory = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new \RecursiveIteratorIterator($directory);
    $matches = new \RegexIterator($iterator, $regex, \RecursiveRegexIterator::GET_MATCH);

    $files = [];
    foreach ($matches as $match) {
      $files[] = $match[0];
    }
    return $files;
  }
}
