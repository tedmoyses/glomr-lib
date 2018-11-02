<?php

use Glomr\Test\GlomrTestCase;
use Glomr\Build\BladeBuilder;
use Glomr\Build\BuildContext;

class BladeBuilderTest extends GlomrTestCase {
  public function setUp(){
    //$this->buildContext = $this->getCleanBuildContext();
    $this->mockBuildContext = $this->getMockBuilder(BuildContext::class)
      ->disableOriginalConstructor()
      ->setMethods(['getCachePath', 'getPath', 'fetchSourceFiles'])
      ->getMock();

    $this->mockBuildContext->expects($this->atLeastOnce())
      ->method('getPath')
      ->withConsecutive(['source'], ['source'], ['build'], ['source'])
      ->willReturnOnConsecutiveCalls($this->sourcePath, $this->sourcePath, $this->buildPath, $this->sourcePath);


    /*
      $this->mockBuildContext->expects($this->atLeastOnce())
      ->method('getPath')
      ->with('build')
      ->will($this->returnValue($this->buildPath));
    */

    $this->mockBuildContext->expects($this->atLeastOnce())
      ->method('getCachePath')
      ->with('blade')
      ->will($this->returnValue($this->cachePath . '/blade'));

    $this->mockBuildContext->expects($this->atLeastOnce())
      ->method('fetchSourceFiles')
      ->will($this->returnValue(['templates/test.blade.php']));

    mkdir($this->sourcePath . '/layouts', 0777, true);
    mkdir($this->sourcePath . '/templates', 0777, true);
    mkdir($this->cachePath . '/blade', 0777, true);
    $this->fixture = new BladeBuilder($this->mockBuildContext);

    //$this->fixture->setContext('templates');
    file_put_contents($this->sourcePath . '/layouts/master.blade.php', "<html><head><title>Testing Blade Builder</title></head><body>
    @yield('content')
    </body></html>");
    file_put_contents($this->sourcePath . '/templates/test.blade.php', "@extends('layouts.master')
    @section('content')
    <p>Test content</p>
    <p>{{\$testBuildVariable}}</p>
    @endSection");
  }

  public function testBuild() {



    $this->fixture->build(['testBuildVariable' => 'testBuildVariableValue']);
    $outputFilePath = $this->buildPath . '/test.html';
    $this->assertFileExists($outputFilePath);
    $content = file_get_contents($outputFilePath);
    $this->assertContains('<title>Testing Blade Builder</title>', $content);
    $this->assertContains('<p>Test content</p>', $content);
    $this->assertContains('<p>testBuildVariableValue</p>', $content);
  }
}
