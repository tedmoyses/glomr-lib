<?php

use Glomr\Test\GlomrTestCase;
use Glomr\Watch\InotifyEventsWatcher;

/**
 * @requires extension inotify
 */
class InotifyWatcher extends GlomrTestCase {
  public function setUp(){
    $this->buildContext = $this->getCleanBuildContext();
    $this->fixture = new InotifyEventsWatcher($this->buildContext);
  }

  public function testWatchBuild(){
    popen("php tests/bin/writefile.php test.txt 'Testing!' &", "r");
    //Watcher should eventually return true once the above hack has created a file
    $this->assertTrue($this->fixture->watchBuild());
  }
}
