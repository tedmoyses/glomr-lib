<?php

use Glomr\Test\GlomrTestCase;
use Glomr\Watch\InotifyEventsWatcher;
use Glomr\Build\BuildContext;


/**
 * @requires extension inotify
 */
class InotifyWatcher extends GlomrTestCase {
  public function setUp() :void {
    //$this->buildContext = $this->getCleanBuildContext();
    $this->bc = $this->getMockBuilder(BuildContext::class)
      ->disableOriginalConstructor()
      ->setMethods(['fetchSourceDirectories', 'getPath'])
      ->getMock();

    $this->bc->expects($this->once())
      ->method('fetchSourceDirectories')
      ->will($this->returnValue([$this->sourcePath . '/testing']));

    $this->bc->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('source')
      ->will($this->returnValue($this->sourcePath));

    mkdir($this->sourcePath . '/testing', 0777, true);
    $this->fixture = new InotifyEventsWatcher($this->bc);
  }

  /**
   * WARNING!!! this uses a filthy hack to asyncronously create a file in the
   * source directory. By rights it should at least use the build context build
   * path - perhaps this should be a method on GlomrTestCase??
   * Just need filesystem event to enure inotify picks it up
   * @return [type] [description]
   */
  public function testWatch(){
    $handle = popen("php tests/bin/writefile.php $this->sourcePath/test.txt, 10000 &", "r");
    //Watcher should eventually return true once the above hack has created a file
    $this->assertTrue($this->fixture->watch());
  }

  protected function tearDown() :void {
    $this->delTree($this->sourcePath);
  }
}
