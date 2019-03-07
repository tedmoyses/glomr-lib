<?php

namespace Glomr\Build;
use Glomr\Build\BuilderInterface;
use Glomr\Build\Filter\JsMinifyFilter;
use Glomr\Build\Filter\CssMinifyFilter;
use Glomr\Log\Logr;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\AssetCache;
use Assetic\Cache\FilesystemCache;

class AssetBuilder implements BuilderInterface {
  private $buildContext;
  private $cssPath = 'css/';
  private $jsPath = 'js/';
  private $imagesPath = 'images/';
  private $compression = false;
  private $sourceContext = 'assets/';
  private $cache;

  public function __construct(\Glomr\Build\BuildContext $buildContext, array $options = []){
    $this->buildContext = $buildContext;
    if(isset($options['compression'])) $this->setCompression($options['compression']);
    if(isset($options['context'])) $this->context = $options['context'];

    if(isset($options['jsPath'])) $this->jsPath = $options['jsPath'];
    if(isset($options['cssPath'])) $this->cssPath = $options['cssPath'];
    if(isset($options['imagesPath'])) $this->imagesPath = $options['imagesPath'];

  }

  public function setCompression(string $compression){
    switch($compression) {
      case 'low':
        $this->compression = 'low';
        break;
      case 'high':
        $this->compression = 'high';
        break;
      default:
        $this->compression = false;
    }
  }

  public function beforeBuild(){
    //clear out any css or js files in build - if production
    if($this->buildContext->getEnv() == 'production'){
      foreach($this->getBuiltAssets() as $file){
        unlink($file);
      }
    }
  }

  public function build(array $buildArgs = []) :array{
    if(!isset($buildArgs['buildID'])){
      $buildArgs['buildID'] = substr(sha1(microtime()), 0, 7);
    }

    if($this->buildContext->getEnv() == 'production') {
      $this->buildJsAssets($buildArgs);
      $this->buildCssAssets($buildArgs);
    } else {
      $this->copyDirectoryFilesIfNewer($this->sourceContext . $this->jsPath);
      $this->copyDirectoryFilesIfNewer($this->sourceContext . $this->cssPath);
    }

    $this->copyDirectoryFilesIfNewer($this->sourceContext . $this->imagesPath);

    return array_merge($buildArgs, ['assets' => $this->getBuiltAssets()]);
  }

	private function getBuiltAssets($from="") :array {
		$strip = $this->buildContext->getPath('build');
		return array_map(function($item) use ($strip) {
		  return str_replace($strip, '', $item);
		}, $this->buildContext->fetchBuildFiles(
      $this->sourceContext,
      '/^.*(\.css)|(\.js)$/i'
    ));
  }

  private function buildJsAssets(array $buildArgs){
    switch($this->compression){
      case 'low':
        $jsFilters = [new JsMinifyFilter()];
        break;
      case 'high':
        $jsFilters = [new \Assetic\Filter\JSqueezeFilter()];
        break;
      default:
        $jsFilters = [];
        break;
    }

    Logr::debug("Using JS filters:", $jsFilters);

    $jsCollection = new AssetCollection([], $jsFilters);
    try {
      $jsFiles = $this->buildContext->fetchSourceFiles($this->sourceContext . $this->jsPath, "/^.+\.js$/i");
    } catch (\Exception $e) {
      Logr::warn("There are no JS files in " . $this->buildContext->getPath('source') . "/" . $this->jsPath );
      return;
    }
    foreach($jsFiles as $file){
      $jsCollection->add(new AssetCache(new FileAsset($file), $this->getCache()));
    }
    $this->buildContext->putBuildFile(
      $this->sourceContext . $this->jsPath . "/{$buildArgs['buildID']}-site.js",
      $jsCollection->dump()
    );
  }

  private function buildCssAssets(array $buildArgs){
    if($this->compression !== '') $cssFilters = [new CssMinifyFilter()];
    else $cssFilters = [];

    Logr::debug("Using CSS filters:", $cssFilters);

    $cssCollection = new AssetCollection([], $cssFilters);
    try{
      $cssFiles = $this->buildContext->fetchSourceFiles($this->sourceContext . $this->cssPath, "/^.+\.css$/i");
    } catch (\Exception $e){
      Logr::warn("There are no CSS assets files in " . $this->buildContext->getPath('source') . "/" . $this->cssPath);
      return;
    }
    foreach($cssFiles as $file){
      $cssCollection->add(new AssetCache(new FileAsset($file), $this->getCache()));
    }

    $this->buildContext->putBuildFile(
      $this->sourceContext . $this->cssPath . "/{$buildArgs['buildID']}-site.css",
      $cssCollection->dump()
    );
  }

  private function copyDirectoryFilesIfNewer($dir){
    foreach($this->buildContext->fetchSourceFiles($dir) as $file){
      $destination = str_replace($this->buildContext->getPath('source'), '', $file);
      if($this->isFileNewer($file, $destination)) {
        $this->buildContext->putBuildFile($destination, file_get_contents($file));
      }
    }
  }

  private function isFileNewer($src, $dest) :bool{
    return (file_exists($dest) && filemtime($src) > filemtime($dest)) || !file_exists($dest);
  }

  private function getCache() :FilesystemCache {
    if(!$this->cache){
      $this->cache = new FilesystemCache($this->buildContext->getCachePath('assetic'));
    }
    return $this->cache;
  }
}
