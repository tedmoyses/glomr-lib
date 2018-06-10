<?php
use Glomr\Test\GlomrTestCase;
use Glomr\Build\BuildService;

class BuildServiceTest extends GlomrTestCase {
  public function setUp(){
    $this->fixture = new BuildService($this->getCleanBuildContext());
  }

  public function testCanBuild(){
    $mockBuilder = $this->getMockBuilder('Glomr\Build\BladeBuilder')
      ->disableOriginalConstructor()
      ->setMethods(['build'])
      ->getMock();

    $this->fixture->registerBuilder($mockBuilder);
    $this->assertTrue($this->fixture->build());
  }

  public function testCanRunServer(){
    $this->fixture->runServer();
    exec("pgrep -f 'php -S'", $output, $return);
    $this->assertTrue(is_numeric($return));
  }
}
