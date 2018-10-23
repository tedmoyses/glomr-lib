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
  private $siteJsFile;
  private $siteCssFile;
  private $cssPath;
  private $jsPath;
  private $imagesPath;

  public function __construct(\Glomr\Build\BuildContext $buildContext, string $compression = ''){
    $this->buildContext = $buildContext;
    $this->compression = $compression;
    $this->jsPath = $this->buildContext->getPath('assetJs');
    $this->cssPath = $this->buildContext->getPath('assetCss');
    $this->imagesPath = $this->buildContext->getPath('assetImages');
  }

  private function setSiteJsFile(string $buildId = ""){
    $this->siteJsFile = $this->jsPath . "/$buildId-site.js";
  }
  private function setSiteCssFile(string $buildId = ""){
    $this->siteCssFile = $this->cssPath . "/$buildId-site.css";
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
        $this->compression = '';
    }
  }

  public function beforeBuild(){
    //clear out any css or js files
    //$path = $this->buildContext->getPath('build') . '/assets/';
    $path = $this->buildContext->getPath('build');
    $files = array_merge(glob($path ."/" . $this->cssPath . "/*"),
      glob($path . "/" . $this->jsPath . "/*"));
    foreach($files as $file){
      unlink($file);
    }
    $this->cache = new FilesystemCache($this->buildContext->getPath('cache') . DIRECTORY_SEPARATOR . 'assetic');
  }

  public function build(array $buildArgs = []){
    if(!isset($buildArgs['buildID'])){
      $buildArgs['buildID'] = substr(sha1(microtime()), 0, 7);
    }
    $this->setSiteJsFile($buildArgs['buildID']);
    $this->setSiteCssFile($buildArgs['buildID']);

    if($this->buildContext->getEnv() == 'production') {
      $this->buildJsAssets();
      $this->buildCssAssets();
    } else {
      $this->moveFiles($this->jsPath);
      $this->moveFiles($this->cssPath);
    }

    $this->moveFiles($this->imagesPath);

    $buildArgs['assets'] = array_merge($this->getBuiltAssets($this->jsPath),
      $this->getBuiltAssets($this->cssPath));
    return $buildArgs;
  }

  private function getBuiltAssets($from){
    $files = [];
    $build = $this->buildContext->getPath('build');
    $directory = new \RecursiveDirectoryIterator($build . "/$from",
      \RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new \RecursiveIteratorIterator($directory);
    foreach(new \RecursiveIteratorIterator($directory) as $file){
      $files[] = str_replace($build, "", "$file");
    }
    return $files;
  }

  private function buildJsAssets(){
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
      $jsFiles = $this->buildContext->fetchSourceFiles($this->jsPath, "/^.+\.js$/i");
    } catch (\Exception $e) {
      Logr::warn("There are no JS files in " . $this->buildContext->getPath('source') . "/" . $this->$jsPath );
      return;
    }
    foreach($jsFiles as $file){
      $jsCollection->add(new AssetCache(new FileAsset($file), $this->cache));
    }
    file_put_contents($this->setupPath($this->siteJsFile), $jsCollection->dump());
  }

  private function buildCssAssets(){
    if($this->compression !== '') $cssFilters = [new CssMinifyFilter()];
    else $cssFilters = [];

    Logr::debug("Using CSS filters:", $cssFilters);

    $cssCollection = new AssetCollection([], $cssFilters);
    try{
      $cssFiles = $this->buildContext->fetchSourceFiles($this->cssPath, "/^.+\.css$/i");
    } catch (\Exception $e){
      Logr::warn("There are no CSS assets files in " . $this->buildContext->getPath('source') . "/" . $this->cssPath);
      return;
    }
    foreach($cssFiles as $file){
      $cssCollection->add(new AssetCache(new FileAsset($file), $this->cache));
    }
    file_put_contents($this->setupPath($this->siteCssFile), $cssCollection->dump());
  }

  private function moveFiles($dir){
    // is copy good enough for this or should it be stream based?
    if(is_dir($this->buildContext->getPath('source') . '/' . $dir)) {
      $files = $this->buildContext->fetchSourceFiles($dir);
      foreach($files as $file){
        $this->moveFile($file);
      }
    }
  }

  private function moveFile($file){
    $dest = $this->setupPath(str_replace($this->buildContext->getPath('source'), "", $file));
    if((file_exists($dest) && microtime($file) > microtime($dest)) || !file_exists($dest)) {
      copy($file, $dest);
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
