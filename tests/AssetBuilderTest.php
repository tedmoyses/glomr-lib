<?php

use Glomr\Test\GlomrTestCase;
use Glomr\Build\AssetBuilder;
use MatthiasMullie\Minify;


class AssetBuilderTest extends GlomrTestCase {
  public function setUp(){
    $this->buildContext = $this->getCleanBuildContext();
    $this->fixture = new AssetBuilder($this->buildContext);
    mkdir($this->buildContext->getPath('source') . '/assets/js', 0777, true);
    mkdir($this->buildContext->getPath('source') . '/assets/css', 0777, true);
    mkdir($this->buildContext->getPath('source') . '/assets/images', 0777, true);
    $this->buildContext->setEnv('production');

    $js1 = <<<EOT
/**
 * First comment
 */
function test (testvar) {
  return testvar
}
EOT;

    $js2 = <<<EOT
//Second comment
function anothertest (foo) {
  return foo + "bar";
}
EOT;

    // add a pair of js files
    file_put_contents($this->buildContext->getPath('source'). '/assets/js/Bsecond.js', $js1);
    file_put_contents($this->buildContext->getPath('source'). '/assets/js/Cthird.js', $js2);

    // make some css files to join up
    file_put_contents($this->buildContext->getPath('source'). '/assets/css/first.css', 'body { color: red } ');
    file_put_contents($this->buildContext->getPath('source'). '/assets/css/second.css', 'body { font-size: 20px } ');

    // make some images
    file_put_contents($this->buildContext->getPath('source'). '/assets/images/image.png', 'png');
    file_put_contents($this->buildContext->getPath('source'). '/assets/images/image.gif', 'gif');
    file_put_contents($this->buildContext->getPath('source'). '/assets/images/image.jpg', 'jpg');
  }

  public function testBeforeBuildRemovesFiles(){
    $this->fixture->beforeBuild();
    $path = $this->buildContext->getPath('build') . '/assets/';
    $files = glob("{{$path}css/*,{$path}js/*}", GLOB_BRACE);
    $this->assertEquals(0, count($files));
  }

  public function testBuildJsNoCompression(){
    $this->fixture->beforeBuild();
    $this->fixture->build(['buildID' => 'testbuild']);
    $scriptOutputPath = $this->buildContext->getPath('build') . '/assets/js/testbuild-site.js';

    $this->assertFileExists($scriptOutputPath);

    $scriptContent = file_get_contents($scriptOutputPath);

    //testing var and comments present in final files
    $this->assertContains('First comment', $scriptContent);
    $this->assertContains('testvar', $scriptContent);
    $this->assertContains('//Second comment', $scriptContent );
    $this->assertContains('anothertest', $scriptContent);
    $this->assertContains('foo + "bar"', $scriptContent);
  }

  public function testBuildJsLowCompression(){
    $this->fixture->setCompression('low');
    $this->fixture->beforeBuild();
    $this->fixture->build(['buildID' => 'testbuild']);
    $scriptOutputPath = $this->buildContext->getPath('build') . '/assets/js/testbuild-site.js';

    $this->assertFileExists($scriptOutputPath);

    $scriptContent = file_get_contents($scriptOutputPath);

    //testing var and comments are NOT present in final files
    $this->assertFalse(strpos($scriptContent, 'First comment'));
    $this->assertContains('testvar', $scriptContent);
    $this->assertFalse(strpos($scriptContent, '//Second comment'));
    $this->assertContains('anothertest', $scriptContent);
    $this->assertContains('foo+"bar"', $scriptContent);
  }

  public function testBuildJsHighCompression(){
    $this->fixture->setCompression('high');
    $this->fixture->beforeBuild();
    $this->fixture->build(['buildID' => 'testbuild']);
    $scriptOutputPath = $this->buildContext->getPath('build') . '/assets/js/testbuild-site.js';

    $this->assertFileExists($scriptOutputPath);

    $scriptContent = file_get_contents($scriptOutputPath);

    //testing var and comments are NOT present in final files
    //and var names should be replaced by stuff
    $this->assertFalse(strpos($scriptContent, 'First comment'));
    $this->assertFalse(strpos($scriptContent, 'testvar'));
    $this->assertFalse(strpos($scriptContent, '//Second comment'));
    $this->assertContains('anothertest', $scriptContent);
    $this->assertFalse(strpos($scriptContent, 'foo+"bar"'));
  }

  public function testBuildCssNoCompression(){
    $this->fixture->beforeBuild();
    $this->fixture->build(['buildID' => 'testbuild']);
    $scriptOutputPath = $this->buildContext->getPath('build') . '/assets/css/testbuild-site.css';

    $this->assertFileExists($scriptOutputPath);

    $scriptContent = file_get_contents($scriptOutputPath);

    //check we still have our declarations - all in one file
    $this->assertContains('body { color: red } ', $scriptContent);
    $this->assertContains('body { font-size: 20px } ', $scriptContent);
  }

  public function testBuildCssWithCompression(){
    $this->fixture->setCompression('low');
    $this->fixture->beforeBuild();
    $this->fixture->build(['buildID' => 'testbuild']);
    $scriptOutputPath = $this->buildContext->getPath('build') . '/assets/css/testbuild-site.css';

    $this->assertFileExists($scriptOutputPath);

    $scriptContent = file_get_contents($scriptOutputPath);

    //check we still have our declarations wihtout wihitespace - all in one file
    $this->assertContains('body{color:red}', $scriptContent);
    $this->assertContains('body{font-size:20px}', $scriptContent);
  }

  public function testBuildImages() {
    $this->fixture->beforeBuild();
    $this->fixture->build(['buildID' => 'testbuild']);
    $pngImage = $this->buildContext->getPath('build') . '/assets/images/image.png';
    $gifImage = $this->buildContext->getPath('build') . '/assets/images/image.gif';
    $jpgImage = $this->buildContext->getPath('build') . '/assets/images/image.jpg';

    $this->assertFileExists($pngImage);
    $this->assertFileExists($gifImage);
    $this->assertFileExists($jpgImage);
  }

  public function testDevBuildPreservesAssets(){
    $this->buildContext->setEnv('dev');
    $this->fixture->build(['buildID' => 'testbuild']);

    $this->assertFileExists($this->buildContext->getPath('build') . '/assets/js/Bsecond.js');
    $this->assertFileExists($this->buildContext->getPath('build') . '/assets/js/Cthird.js');
    $this->assertFileExists($this->buildContext->getPath('build') . '/assets/css/first.css');
    $this->assertFileExists($this->buildContext->getPath('build') . '/assets/css/second.css');
  }

}
