<?php

use Glomr\Test\GlomrTestCase;

class BuildContextTest extends GlomrTestCase {

  public function setUp() :void {
    $this->fixture = $this->getCleanBuildContext();
  }

  public function testCanFindFiles(){
    $this->getFs()->write($this->sourcePath. "/Atest.txt", "this is just a test");
    $this->getFs()->write($this->sourcePath. "/Btest.html", "this is just a test");
    $files = $this->fixture->fetchSourceFiles();
    $this->assertEquals(2, count($files));
    $this->assertEquals($this->sourcePath . "/Atest.txt", $files[0]);
    $this->assertEquals($this->sourcePath . "/Btest.html", $files[1]);
  }

  public function testCanFindFilesWithRegex() {
    $this->getFs()->write($this->sourcePath. "/test.html", "this is just a test");
    $this->getFs()->write($this->sourcePath. "/test.txt", "this is just a test");
    $files = $this->fixture->fetchSourceFiles("", "/^.+\.html$/i");
    $this->assertEquals(1, count($files));
    $this->assertEquals($this->sourcePath . "/test.html", $files[0]);
  }

  public function testCanFindFilesWithContext() {
    $this->getFs()->write($this->sourcePath. "/testdirectory/test.html", "this is just a test");
    $this->getFs()->write($this->sourcePath. "/other.html", "this is just a test");
    $files = $this->fixture->fetchSourceFiles("testdirectory", "/^.+\.html$/i");
    $this->assertEquals(1, count($files));
    $this->assertEquals($this->sourcePath . "/testdirectory/test.html", $files[0]);
    $this->delTree('testdirectory');
  }

  public function testCanGetFileMtime(){
    $file = $this->sourcePath . '/testing.txt';
    file_put_contents($file, '');
    $this->assertEquals(filemtime($file), $this->fixture->mtime($file));
    unlink($file);
  }

  public function testCanFindDirectories(){
    $this->getFs()->createDir($this->sourcePath . '/testdirectory');
    $dirs = $this->fixture->fetchSourceDirectories();
    $this->assertEquals(1, count($dirs));
    $this->assertEquals($this->sourcePath . "/testdirectory", $dirs[0]);
    $this->assertTrue(is_dir($dirs[0]));
  }

  protected function tearDown() :void {
    $this->delTree($this->buildPath);
    $this->delTree($this->sourcePath);
  }
}
