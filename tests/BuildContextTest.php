<?php

use Glomr\Test\GlomrTestCase;

class BuildContextTest extends GlomrTestCase {

  public function setUp(){
    $this->fixture = $this->getCleanBuildContext();
  }

  public function testCanSetPath () {
    $this->fixture->setPath('test', 'ickle');
    $this->assertEquals($this->fixture->getPath('test'), 'ickle');
  }

  public function testCanFindFiles(){
    file_put_contents($this->sourcePath. "/Atest.txt", "this is just a test");
    file_put_contents($this->sourcePath. "/Btest.html", "this is just a test");
    $files = $this->fixture->fetchSourceFiles();
    $this->assertEquals(2, count($files));
    $this->assertEquals($this->sourcePath . "/Atest.txt", $files[0]);
    $this->assertEquals($this->sourcePath . "/Btest.html", $files[1]);
  }

  public function testCanFindFilesWithRegex() {
    file_put_contents($this->sourcePath. "/test.html", "this is just a test");
    file_put_contents($this->sourcePath. "/test.txt", "this is just a test");
    $files = $this->fixture->fetchSourceFiles("", "/^.+\.html$/i");
    $this->assertEquals(1, count($files));
    $this->assertEquals($this->sourcePath . "/test.html", $files[0]);
  }

  public function testCanFindFilesWithContext() {
    mkdir($this->sourcePath . "/testdirectory", 0777, true);
    file_put_contents($this->sourcePath. "/testdirectory/test.html", "this is just a test");
    file_put_contents($this->sourcePath. "/other.html", "this is just a test");
    $files = $this->fixture->fetchSourceFiles("testdirectory", "/^.+\.html$/i");
    $this->assertEquals(1, count($files));
    $this->assertEquals($this->sourcePath . "/testdirectory/test.html", $files[0]);
  }

  public function testCanFindDirectories(){
    mkdir($this->sourcePath . "/testdirectory", 0777, true);
    file_put_contents($this->sourcePath . "/testdirectory/test.txt", "Testing!");
    $dirs = $this->fixture->fetchSourceDirectories();
    $this->assertEquals(1, count($dirs));
    $this->assertEquals($this->sourcePath . "/testdirectory", $dirs[0]);
    $this->assertTrue(is_dir($dirs[0]));
  }

  public function tearDown() {
    $this->delTree($this->buildPath);
    $this->delTree($this->sourcePath);
  }
}
