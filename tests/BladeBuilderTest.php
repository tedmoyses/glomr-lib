<?php

use Glomr\Test\GlomrTestCase;
use Glomr\Build\BladeBuilder;
use Glomr\Build\BuildContext;

class BladeBuilderTest extends GlomrTestCase {
  protected function setUp(){

    if(!is_dir($this->sourcePath . '/templates')) mkdir($this->sourcePath . '/templates', 0777, true);
    if(!is_dir($this->cachePath . '/blade')) mkdir($this->cachePath . '/blade', 0777, true);

    file_put_contents($this->sourcePath . '/templates/test.blade.php', "@extends('layout')
    @section('content')
    <p>Test content</p>
    <p>{{\$testBuildVariable}}</p>
    @endSection");

    file_put_contents($this->sourcePath . '/layout.blade.php', "<html><head><title>Testing Blade Builder</title></head><body>
    @yield('content')
    </body></html>");
  }

  public function testBuild() {

    $mockBuildContext = $this->getMockBuilder(BuildContext::class)
      ->disableOriginalConstructor()
      ->setMethods(['getCachePath', 'getPath', 'fetchSourceFiles', 'putBuildFile'])
      ->getMock();

    $mockBuildContext->expects($this->atLeastOnce())
      ->method('getPath')
      ->withConsecutive(['source'], ['source'], ['build'], ['build'])
      ->willReturnOnConsecutiveCalls($this->sourcePath, $this->sourcePath, $this->buildPath, $this->buildPath);

    $mockBuildContext->expects($this->atLeastOnce())
      ->method('getCachePath')
      ->with('blade')
      ->will($this->returnValue($this->cachePath . '/blade'));

    $mockBuildContext->expects($this->atLeastOnce())
      ->method('fetchSourceFiles')
      ->will($this->returnValue(['templates/test.blade.php']));

    $mockBuildContext->expects($this->atLeastOnce())
      ->method('putBuildFile')
      ->with($this->buildPath . '/test.html', $this->callback(function ($arg){
        $this->isInstanceOf(\Illuminate\View\View::class, $arg);
        $content = $arg->__toString();
        $this->assertContains('<title>Testing Blade Builder</title>', $content);
        $this->assertContains('<p>Test content</p>', $content);
        $this->assertContains('<p>testBuildVariableValue</p>', $content);
        return true;
      }));

    $fixture = new BladeBuilder($mockBuildContext);

    $fixture->build(['testBuildVariable' => 'testBuildVariableValue']);
  }

  protected function tearDown(){
    unlink($this->sourcePath . '/templates/test.blade.php');
    unlink($this->sourcePath . '/layout.blade.php');
    rmdir($this->sourcePath . '/templates');
    rmdir($this->sourcePath);
    foreach(glob($this->cachePath . '/blade/*') as $file) unlink($file);
    rmdir($this->cachePath . '/blade');
    rmdir($this->cachePath);
  }
}
