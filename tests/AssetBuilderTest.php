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
    $this->fixture->build();
    $scriptOutputPath = $this->buildContext->getPath('build') . '/assets/js/site.js';

    $this->assertTrue(file_exists($scriptOutputPath));

    $scriptContent = file_get_contents($scriptOutputPath);

    //testing var and comments present in final files
    $this->assertTrue(strpos($scriptContent, 'First comment') !== false);
    $this->assertTrue(strpos($scriptContent, 'testvar') !== false);
    $this->assertTrue(strpos($scriptContent, '//Second comment') !== false);
    $this->assertTrue(strpos($scriptContent, 'anothertest') !== false);
    $this->assertTrue(strpos($scriptContent, 'foo + "bar"') !== false);
  }

  public function testBuildJsLowCompression(){
    putenv('compression=low');
    $this->fixture->build();
    $scriptOutputPath = $this->buildContext->getPath('build') . '/assets/js/site.js';

    $this->assertTrue(file_exists($scriptOutputPath));

    $scriptContent = file_get_contents($scriptOutputPath);

    //testing var and comments are NOT present in final files
    $this->assertTrue(strpos($scriptContent, 'First comment') === false);
    $this->assertTrue(strpos($scriptContent, 'testvar') !== false);
    $this->assertTrue(strpos($scriptContent, '//Second comment') === false);
    $this->assertTrue(strpos($scriptContent, 'anothertest') !== false);
    $this->assertTrue(strpos($scriptContent, 'foo+"bar"') !== false);
  }

  public function testBuildJsHighCompression(){
    putenv('compression=high');
    $this->fixture->build();
    $scriptOutputPath = $this->buildContext->getPath('build') . '/assets/js/site.js';

    $this->assertTrue(file_exists($scriptOutputPath));

    $scriptContent = file_get_contents($scriptOutputPath);

    //testing var and comments are NOT present in final files
    //and var names should be replaced by stuff
    $this->assertTrue(strpos($scriptContent, 'First comment') === false);
    $this->assertTrue(strpos($scriptContent, 'testvar') === false);
    $this->assertTrue(strpos($scriptContent, '//Second comment') === false);
    $this->assertTrue(strpos($scriptContent, 'anothertest') !== false);
    $this->assertTrue(strpos($scriptContent, 'foo+"bar"') === false);
  }

  public function testBuildCssNoCompression(){
    putenv('compression');
    $this->fixture->build();
    $scriptOutputPath = $this->buildContext->getPath('build') . '/assets/css/site.css';

    $this->assertTrue(file_exists($scriptOutputPath));

    $scriptContent = file_get_contents($scriptOutputPath);

    //check we still have our declarations - all in one file
    $this->assertTrue(strpos($scriptContent, 'body { color: red } ') !== false);
    $this->assertTrue(strpos($scriptContent, 'body { font-size: 20px } ') !== false);
  }

  public function testBuildCssWithCompression(){
    putenv('compression=low');
    $this->fixture->build();
    $scriptOutputPath = $this->buildContext->getPath('build') . '/assets/css/site.css';

    $this->assertTrue(file_exists($scriptOutputPath));

    $scriptContent = file_get_contents($scriptOutputPath);

    //check we still have our declarations wihtout wihitespace - all in one file
    $this->assertTrue(strpos($scriptContent, 'body{color:red}') !== false);
    $this->assertTrue(strpos($scriptContent, 'body{font-size:20px}') !== false);
  }

  public function testBuildImages() {
    $this->fixture->build();
    $pngImage = $this->buildContext->getPath('build') . '/assets/images/image.png';
    $gifImage = $this->buildContext->getPath('build') . '/assets/images/image.gif';
    $jpgImage = $this->buildContext->getPath('build') . '/assets/images/image.jpg';

    $this->assertTrue(file_exists($pngImage));
    $this->assertTrue(file_exists($gifImage));
    $this->assertTrue(file_exists($jpgImage));
  }

}
