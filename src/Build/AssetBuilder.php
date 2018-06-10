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
  private $siteJsFile = 'assets/js/site.js';
  private $siteCssFile = 'assets/css/site.css';

  public function __construct(\Glomr\Build\BuildContext $buildContext){
    $this->buildContext = $buildContext;
  }

  public function beforeBuild(){
    //clear out any css or js files
    $path = $this->buildContext->getPath('build') . '/assets/';
    $files = array_merge(glob("${path}css/*"), glob("${path}js/*"));
    foreach($files as $file){
      unlink($file);
    }
  }

  public function build(array $buildArgs = []){
    $cache = new FilesystemCache($this->buildContext->getPath('cache') . DIRECTORY_SEPARATOR . 'assetic');

    switch(getEnv('compression')){
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
    foreach($this->buildContext->fetchSourceFiles("assets/js", "/^.+\.js$/i") as $file){
      $jsCollection->add(new AssetCache(new FileAsset($file), $cache));
    }
    file_put_contents($this->setupPath($this->siteJsFile), $jsCollection->dump());

    if(getenv('compression') !== false) $cssFilters = [new CssMinifyFilter()];
    else $cssFilters = [];

    Logr::debug("Using CSS filters:", $cssFilters);

    $cssCollection = new AssetCollection([], $cssFilters);
    foreach($this->buildContext->fetchSourceFiles("assets/css", "/^.+\.css$/i") as $file){
      $cssCollection->add(new AssetCache(new FileAsset($file), $cache));
    }
    file_put_contents($this->setupPath($this->siteCssFile), $cssCollection->dump());

    // do images - no compression for now
    // is copy good enough for this or should it be stream based?
    if(is_dir($this->buildContext->getPath('source') . "/assets/images")) {
      $images = $this->buildContext->fetchSourceFiles('assets/images');
      foreach($images as $src){
        $dest = $this->setupPath(str_replace($this->buildContext->getPath('source'), "", $src));
        if((file_exists($dest) && mtime($src) > mtime($dest)) || !file_exists($dest)) {
          copy($src, $dest);
        }
      }
    }
  }

  private function setupPath($path){
    $destination = $this->buildContext->getPath('build') .
      DIRECTORY_SEPARATOR .
      $path;

    if (!file_exists(dirname($destination))) {
      mkdir(dirname($destination), 0777, true);
    }
    return $destination;
  }
}
