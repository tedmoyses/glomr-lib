<?php

use Glomr\Test\GlomrTestCase;
use Glomr\Watch\PollWatcher;

class PolWatcherTest extends GlomrTestCase {
  public function setUp(){

    $this->fixture = new PollWatcher($this->getCleanBuildContext());
  }

  /**
   * WARNING!!! this uses a filthy hack to asyncronously create a file in the
   * source directory. By rights it shoudl at least use the build context build
   * path - perhaps this sould be a method on GlomrTestCase??
   * @return [type] [description]
   */
  public function testPollWatcher (){
    popen("php tests/bin/writefile.php test.txt 'Testing!' &", "r");
    //Watcher should eventually return true once the above hack has created a file
    $this->assertTrue($this->fixture->watchBuild());
  }
}
