<?php

namespace Glomr\Test;
use Glomr\Build\BuildContext;
use PHPUnit\Framework\TestCase;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

class GlomrTestCase extends TestCase{

  protected $buildPath = "tests/build";
  protected $sourcePath = "tests/source";
  protected $cachePath = "tests/cache";
  private $_fs = null;

  protected function getFs() :Filesystem {
    if($this->_fs === null) $this->_fs = new Filesystem(new Local('tests/'));
    return $this->_fs;
  }

  protected function delTree($dir){
    var_dump($this->getFs()->deleteDir($dir)?
      "detlTree Success for $dir" :
      "delTree fail for $dir"
    );
    return;
    $base = dirname(dirname(__FILE__));
    $path = realpath($dir);

    if(!is_dir($dir) && strpos($path, $base) !== 0){
      throw new \RuntimeException("Canot delete path");
    } else {
      $it = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
      $files = new \RecursiveIteratorIterator($it,
                   \RecursiveIteratorIterator::CHILD_FIRST);
      foreach($files as $file) {
          if ($file->isDir()){
              rmdir($file->getRealPath());
          } else {
              unlink($file->getRealPath());
          }
      }
      rmdir($path);
    }
  }

  protected function getCleanBuildContext(){

    return new BuildContext(
      $this->getFs(),
      $this->sourcePath,
      $this->buildPath,
      $this->cachePath
    );
    $buildContext->setPath('source', $this->sourcePath);
    $buildContext->setPath('build', $this->buildPath);
    $buildContext->setPath('cache', $this->cachePath);
    return $buildContext;
  }

  protected function tearDown(){
    $this->delTree($this->buildPath);
    $this->delTree($this->sourcePath);
    $this->delTree($this->cachePath);
  }

}
