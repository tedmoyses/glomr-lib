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
    'build' => null,
    'source' => null,
    'cache' => null
  ];

  private $_fs = null;

  private $environment = 'dev';

  public function __construct(
    Flysystem $fs,
    string $sourcePath,
    string $buildPath,
    string $cachePath
  ) {
    $this->_fs = $fs;
    $this->setPath('source', $sourcePath);
    $this->setPath('build', $buildPath);
    $this->setPath('cache', $cachePath);
  }

  private function setPath(string $key, string $value){
    if(!$this->_fs->has($value)) $this->_fs->createDir($value);
    $this->paths[$key] = $value;
  }

  public function getPath($path) :string {
    return $this->paths[$path];
  }

  public function setEnv($env){
    if(in_array($env, ['production', 'dev'])) $this->environment = $env;
  }

  public function getEnv() :string{
    return $this->environment;
  }

  public function fetchSourceDirectories() :array {
    return $this->fetchDirectories($this->getPath('source'));
  }

  public function fetchSourceFiles(string $context = "", $regex = "/^.+$/i") :array {
    return $this->fetchFiles($this->getPath('source') . "/$context", $regex);
  }

  /**
   * @TODO needs a test!
   * @param  string $path [description]
   * @return string       [description]
   */
  public function getCachePath(string $path) :string {
    $cachePath = $this->getPath('cache') .'/'. $path;
    $this->_fs->createDir($cachePath);
    return $cachePath;
  }

  public function mtime(string $path) :int {
    return $this->_fs->getTimestamp($path);
  }

  private function fetchDirectories(string $path = '*', $regex =  '/.*/') :array {
    return array_map(
      function ($el) {return $el['path'];},
      array_filter(
        $this->_fs->listContents($path),
        function ($path) use ($regex) {
          return ($path['type'] == 'dir' && preg_match($regex, $path['path']) !== 0);
        }
      )
    );
  }

  private function fetchFiles(string $from, $regex = '/.*/') :array {
    return array_map(
      function ($el) {return $el['path'];},
      array_filter(
        $this->_fs->listContents($from),
        function ($path) use ($regex) {
          return ($path['type'] == 'file' && preg_match($regex, $path['path']) !== 0);
        }
      )
    );
  }
}
