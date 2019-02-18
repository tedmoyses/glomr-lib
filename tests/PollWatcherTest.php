<?php

use Glomr\Test\GlomrTestCase;
use Glomr\Watch\PollWatcher;
use Glomr\Build\BuildContext;

class PolWatcherTest extends GlomrTestCase {
  public function setUp() :void {

    //$this->fixture = new PollWatcher($this->getCleanBuildContext());
    $bc = $this->getMockBuilder(BuildContext::class)
      ->disableOriginalConstructor()
      ->setMethods(['getPath', 'fetchSourceFiles', 'mtime'])
      ->getMock();
    $bc->expects($this->exactly(2))
      ->method('fetchSourceFiles')
      ->will($this->returnValue([$this->sourcePath. '/test.txt']));

    $testfile = $this->sourcePath. '/test.txt';
    mkdir($this->sourcePath);
    file_put_contents($testfile, 'testing');

    $bc->expects($this->exactly(2))
      ->method('mtime')
      ->will($this->onConsecutiveCalls(
        filemtime($testfile),
        999999999999999999)
      );

    $this->fixture = new PollWatcher($bc);

  }

  public function testPollWatcher () :void {
    $this->assertTrue($this->fixture->watch());
    $this->assertTrue($this->fixture->watch());
  }
}
