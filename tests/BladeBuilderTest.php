<?php

use Glomr\Test\GlomrTestCase;
use Glomr\Build\BladeBuilder;

class BladeBuilderTest extends GlomrTestCase {
  public function setUp(){
    $this->buildContext = $this->getCleanBuildContext();
    $this->fixture = new BladeBuilder($this->buildContext);
    //mkdir($this->buildContext->getPath('source') . '/layouts', 0777, true);
    //mkdir($this->buildContext->getPath('source') . '/templates', 0777, true);
    $this->fixture->setContext('templates');
    file_put_contents($this->buildContext->getPath('source') . '/layouts/master.blade.php', "<html><head><title>Testing Blade Builder</title></head><body>
    @yield('content')
    </body></html>");
    file_put_contents($this->buildContext->getPath('source') . '/templates/test.blade.php', "@extends('layouts.master')
    @section('content')
    <p>Test content</p>
    <p>{{\$testBuildVariable}}</p>
    @endSection");
  }

  public function testBuild() {
    $this->fixture->build(['testBuildVariable' => 'testBuildVariableValue']);
    $outputFilePath = $this->buildContext->getPath('build') . '/test.html';
    $this->assertFileExists($outputFilePath);
    $content = file_get_contents($outputFilePath);
    $this->assertContains('<title>Testing Blade Builder</title>', $content);
    $this->assertContains('<p>Test content</p>', $content);
    $this->assertContains('<p>testBuildVariableValue</p>', $content);
  }
}
