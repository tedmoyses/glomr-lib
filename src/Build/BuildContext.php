<?php
namespace Glomr\Build;

use Glomr\Watch\InotifyEventsWatcher;
use Glomr\Watch\PollWatcher;
use Glomr\Log\Logr;
use Illuminate\Filesystem\Filesystem;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem as Flysystem;

class BuildContext {
  private $paths = [
    'build' => './build',
    'source' => './src',
    'cache' => './cache',
    'assetJs' => 'assets/js',
    'assetCss' => 'assets/css',
    'assetImages' => 'assets/images'
  ];

  private $_fs = null;

  private $environment = 'dev';

  public function __construct(Flysystem $fs) {
    $this->_fs = $fs;
  }

  public function setPath(string $key, string $value){
    if(!$this->_fs->has($value)) {
      $this->_fs->createDir($value);
      //var_dump($this->_fs->createDir($value));
      //exit;
      var_dump("Created file: $value");
    }
    $this->paths[$key] = $value;
    return;

    if(!in_array($key, array_keys($this->paths)))
      throw new \RuntimeException("Invalid path name");
    if($key === array_search($value, $this->paths))
      throw new \RuntimeException("Cannot set duplicate path");
    $this->checkPath($value);
    if(is_file($value))
      throw new \RuntimeException("Path cannot be existing file");

    $realpath = realpath($value);
    if ($realpath !== false && strpos($realpath, realpath('./')) !== 0)
      throw new \RuntimeException("Path must be in current working directory");

    if(!file_exists($value)) {
      $realpath = getcwd() . '/' . $value;
      //var_dump($realpath);
      if(!mkdir($realpath, 0777, true))
        throw new \RuntimeException("Cannot create path $realpath");
    }

    $this->paths[$key] = $realpath;

    //Should never happen?
    if(sizeof(array_unique($this->paths)) !== sizeof($this->paths)) throw new \RuntimeException("Duplicate path set");
  }

  public function getPath($path){
    $dir = $this->paths[$path];
    if(!is_dir($dir) && substr($dir, 0, 2) == './'){
      mkdir($dir, 0777, true);
    }
    return $dir;
  }

  public function checkPath($path){
    if(preg_match('/^[\/].*|^\.\/.*|\/\.\.\/+/', $path) === 0) return true;
    else throw new \RuntimeException("Path cannot start with / or ./ or contain /../");
  }


  public function setEnv($env){
    if(in_array($env, ['production', 'dev'])) $this->environment = $env;
  }

  public function getEnv(){
    return $this->environment;
  }

  public function fetchSourceDirectories() :array {
    return $this->fetchDirectories($this->getPath('source'));
    $dirs = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator(
        $this->paths['source'],
        \RecursiveDirectoryIterator::SKIP_DOTS
      ),
      \RecursiveIteratorIterator::SELF_FIRST);
    $return = [];
    foreach($dirs as $dir){
      if(is_dir($dir)) $return[] = $dir->__toString();
    }
    return $return;
  }

  public function fetchSourceFiles(string $context = "", $regex = "/^.+$/i") :array {
    if($this->checkPath($context))
      return $this->fetchFiles($this->getPath('source') . "/$context", $regex);
  }

  private function fetchDirectories(string $path = '*', $regex =  '/.*/') :array {
    return array_filter($this->_fs->listContents($path),
      function ($path) use ($regex) { if($path['type'] == 'dir' && preg_match($regex, $path) !== 0) return true;}
    );
  }

  private function fetchFiles(string $path, $regex = '/.*/') :array {
    return preg_grep($regex, Filesystem::allFiles($path));
  }

  public function getCachePath($path){
    $realpath = $this->getPath('cache') .'/'. $path;
    if($this->checkPath($path) && !Filesystem::isDirectory($realpath))
      Filesystem::makeDir($realpath);
    else
      throw new \RuntimeException("Cache path exists");
    return $realpath;
  }

  private function _getFs() :Flysystem {
    if($this->_fs === null){
      $this->_fs = new Flysystem( new Local(getcwd()));
    }
    return $this->_fs;
  }
}
