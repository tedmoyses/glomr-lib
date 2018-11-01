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
    if($this->_fs === null) $this->_fs = new Filesystem(new Local(getcwd()));
    return $this->_fs;
  }

  protected function delTree($dir){
    $this->getFs()->deleteDir($dir);
  }

  protected function getCleanBuildContext() :BuildContext {

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
