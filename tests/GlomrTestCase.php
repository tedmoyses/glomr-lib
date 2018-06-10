<?php

namespace Glomr\Test;
use Glomr\Build\BuildContext;
use PHPUnit\Framework\TestCase;

class GlomrTestCase extends TestCase{

  protected $buildPath = "./tests/build";
  protected $sourcePath = "./tests/source";
  protected $cachePath = "./tests/cache";

  protected function delTree($dir){
    $base = dirname(dirname(__FILE__));
    $path = realpath($dir);

    if(!is_dir($dir) && strpos($path, $base) !== 0){
      var_dump($dir, $path, $base);
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
    if(is_dir($this->buildPath)){
      $this->delTree($this->buildPath);
    }
    mkdir($this->buildPath, 0777, true);

    if(is_dir($this->sourcePath)){
      $this->delTree($this->sourcePath);
    }
    mkdir($this->sourcePath, 0777, true);

    if(is_dir($this->cachePath)){
      $this->delTree($this->cachePath);
    }
    mkdir($this->cachePath, 0777, true);

    $buildContext = new BuildContext();
    $buildContext->setPath('source', $this->sourcePath);
    $buildContext->setPath('build', $this->buildPath);
    $buildContext->setPath('cache', $this->cachePath);
    return $buildContext;
  }

  public function tearDown(){
    $this->delTree($this->buildPath);
    $this->delTree($this->sourcePath);
    $this->delTree($this->cachePath);
  }

}
