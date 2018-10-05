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
      ->setMethods(['build', 'beforeBuild', 'AfterBuild'])
      ->getMock();
    $mockBuilder->method('build')->will($this->returnValue([]));

    $this->fixture->registerBuilder($mockBuilder);
    $this->assertTrue($this->fixture->build());
  }

  public function testCanRunServer(){
    $this->fixture->runServer('0.0.0.0', 8080, './tests/build', "tests/serve.php");
    exec("pgrep -f 'php -S'", $output, $return);
    $this->assertTrue(is_numeric($return));
  }
}
