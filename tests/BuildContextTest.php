<?php

use Glomr\Test\GlomrTestCase;

class BuildContextTest extends GlomrTestCase {

  public function setUp(){
    $this->fixture = $this->getCleanBuildContext();
  }

  /**
   * @expectedException RuntimeException
   * @return [type] [description]
   */
  public function testSetInvalidPath () {
    $this->fixture->setPath('test', 'ickles');
  }

  /**
   * @expectedException RuntimeException
   * @return [type] [description]
   */
  public function testSetDuplicatePath(){
    $this->fixture->setPath('build', $this->fixture->getPath('source'));
  }

  /**
   * @expectedException RuntimeException
   * @return [type] [description]
   */
  public function testSetAbsolutePath(){
    $this->fixture->setPath('build', "/tmp");
  }

  /**
   * @expectedException RuntimeException
   * @return [type] [description]
   */
  public function testSetRelativePath(){
    $this->fixture->setPath('build', "./../../../tmp");
  }

  public function testSetPath () {
    $this->fixture->setPath('build', 'ickle');
    $this->assertDirectoryExists('ickle');
    //$this->delTree($this->fixture->getPath('build'));
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
    $this->getFs()->put($this->sourcePath. "testdirectory/test.html", "this is just a test");
    $this->getFs()->write($this->sourcePath. "other.html", "this is just a test");
    $files = $this->fixture->fetchSourceFiles("testdirectory", "/^.+\.html$/i");
    $this->assertEquals(1, count($files));
    $this->assertEquals($this->sourcePath . "/testdirectory/test.html", $files[0]);
    $this->getFs()->deleteDir('testdirectory');
  }

  public function testCanFindDirectories(){
    $this->getFs()->createDir('testdirectory');
    $dirs = $this->fixture->fetchSourceDirectories();
    $this->assertEquals(1, count($dirs));
    $this->assertEquals($this->sourcePath . "/testdirectory", $dirs[0]);
    $this->assertTrue(is_dir($dirs[0]));
    $this->getFs()->deleteDir('testdirectory');
  }

  public function tearDown() {
    $this->delTree($this->buildPath);
    $this->delTree($this->sourcePath);
  }
}
