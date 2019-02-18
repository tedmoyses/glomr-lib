<?php

use Glomr\Server\PhpServer;
use PHPUnit\Framework\TestCase;
use Glomr\Test\GlomrTestCase;

class ServerTest extends GlomrTestCase {

  public function setUp() :void {
    $this->buildContext = $this->getCleanBuildContext();
  }

  /**
   * expectedException RuntimeException
   * @return [type] [description]
   */
  public function testServerRejectsBadIp(){
    $this->expectException(RuntimeException::class);
    $server = new PhpServer('999.999.999.999');
    unset($server);
  }

  /**
   * expectedException RuntimeException
   * @return [type] [description]
   */
  public function testServerRejectsBadPath(){
    $this->expectException(RuntimeException::class);
    $server = new PhpServer('0.0.0.0', 9999, 'pathdoesnotexist', "test/serve.php");
    $server = null;
  }

  /**
   * expectedException RuntimeException
   * @return [type] [description]
   */
  public function testServerRejectsBadScript(){
    $this->expectException(RuntimeException::class);
    $server = new PhpServer('0.0.0.0', 9999, './build', "test/notascript.php");
    $server = null;
  }

  public function testServerRunsAndStops(){
    $server = new PhpServer('0.0.0.0', 9999, './tests/build', "tests/serve.php");
    $commandString = trim($server->getPhpCommand());
    $this->assertEquals("php -S 0.0.0.0:9999 -t ./tests/build tests/serve.php", $commandString);
    $pid = $server->getPid();

    exec("pgrep -f '$commandString'", $output, $returnVar);
    $this->assertTrue(in_array($pid, $output));

    // unsetting the server will call __destruct and in turn stop()
    unset($server, $output, $returnVar);

    exec("pgrep -f '$commandString'", $output, $returnVar);
    $this->assertTrue(!in_array($pid, $output));
  }

}
