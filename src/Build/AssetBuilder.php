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

    /*$this->jsPath = $this->buildContext->getPath('assetJs');
    $this->cssPath = $this->buildContext->getPath('assetCss');
    $this->imagesPath = $this->buildContext->getPath('assetImages');
    */
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
        $this->compression = false;
    }
  }

  public function beforeBuild(){
    //clear out any css or js files in build
    $path = $this->buildContext->getPath('build') . '/' . $this->sourceContext;
    $files = array_merge(
      glob($path . $this->cssPath . "/*"),
      glob($path . $this->jsPath . "/*")
    );
    foreach($files as $file){
      unlink($file);
    }
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
      $this->moveFiles($this->sourceContext . $this->jsPath);
      $this->moveFiles($this->sourceContext . $this->cssPath);
    }

    $this->copyDirectoryFilesIfNewer($this->sourceContext . $this->imagesPath);

    $buildArgs['assets'] = array_merge(
      $this->getBuiltAssets($this->sourceContext . $this->jsPath),
      $this->getBuiltAssets($this->sourceContext . $this->cssPath)
    );
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
      $jsFiles = $this->buildContext->fetchSourceFiles($this->sourceContext . $this->jsPath, "/^.+\.js$/i");
    } catch (\Exception $e) {
      Logr::warn("There are no JS files in " . $this->buildContext->getPath('source') . "/" . $this->jsPath );
      return;
    }
    foreach($jsFiles as $file){
      $jsCollection->add(new AssetCache(new FileAsset($file), $this->getCache()));
    }
    file_put_contents($this->setupPath($this->sourceContext . $this->siteJsFile), $jsCollection->dump());
  }

  private function buildCssAssets(){
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
    file_put_contents($this->setupPath($this->sourceContext . $this->siteCssFile), $cssCollection->dump());
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
    if((file_exists($dest) && filemtime($file) > filemtime($dest)) || !file_exists($dest)) {
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

  private function getCache() :FilesystemCache {
    if(!$this->cache){
      $this->cache = new FilesystemCache($this->buildContext->getCachePath('assetic'));
    }
    return $this->cache;
  }
}
