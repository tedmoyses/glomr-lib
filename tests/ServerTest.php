<?php

use Glomr\Server\PhpServer;
use PHPUnit\Framework\TestCase;
use Glomr\Test\GlomrTestCase;

class ServerTest extends GlomrTestCase {

  public function setUp(){
    $this->buildContext = $this->getCleanBuildContext();
  }

  /**
   * @expectedException RuntimeException
   * @return [type] [description]
   */
  public function testServerRejectsBadIp(){
    $server = new PhpServer('999.999.999.999');
    unset($server);
  }

  /**
   * @expectedException RuntimeException
   * @return [type] [description]
   */
  public function testServerRejectsBadPath(){
    $server = new PhpServer('0.0.0.0', 9999, 'pathdoesnotexist');
    $server = null;
  }

  public function testServerRunsAndStops(){
    $server = new PhpServer('0.0.0.0', 9999, './tests/build');
    $commandString = $server->getPhpCommand();
    $pid = $server->getPid();

    exec("pgrep -f '$commandString'", $output, $returnVar);
    $this->assertTrue(in_array($pid, $output));

    // unsetting the server will call __destruct and in turn stop()
    unset($server, $output, $returnVar);

    exec("pgrep -f '$commandString'", $output, $returnVar);
    $this->assertTrue(!in_array($pid, $output));
  }

}
