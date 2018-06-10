<?php

namespace Glomr\Build;

use Glomr\Build\BuilderInterface;
use Jenssegers\Blade\Blade;
use Glomr\Log\Logr;

class BladeBuilder implements BuilderInterface {

  private $regex = '/^.+\.blade\.php$/i';
  private $sourceExtension = ".blade.php";
  private $buildExtension = ".html";
  private $context = "templates";

  private $buildContext;
  private $blade;

  public function __construct(\Glomr\Build\BuildContext $buildContext) {
    $this->buildContext = $buildContext;
    $cachePath = $this->buildContext->getPath('cache');
    $cachePath .= DIRECTORY_SEPARATOR . 'blade';
    if(!file_exists($cachePath) && !is_dir($cachePath)) mkdir($cachePath, 0777, true);
    $this->blade = new Blade($this->buildContext->getPath('source'), $cachePath);
  }

  public function setContext(string $context) {
    $this->context = $context;
  }

  public function build(array $buildArgs = []) {
    foreach($this->buildContext->fetchSourceFiles($this->context, $this->regex) as $viewTemplate){
      $viewName = $this->viewNameFromSource($viewTemplate);
      $destination = $this->buildPathFromSource($viewTemplate);

      if (!file_exists(dirname($destination))) {
        mkdir(dirname($destination), 0777, true);
      }
      try {
        file_put_contents($destination, $this->blade->make($viewName, $buildArgs));
      } catch (\InvalidArgumentException $e ) {
        Logr::log(Logr::Error, "Cannot find view! Name = ${$viewName}, path ${destination}\n");
      }
    }
  }

  public function beforeBuild(){
    return;
  }

  public function afterBuild(){
    return;
  }

  private function viewNameFromSource(string $path) {
    return str_replace('/', '.',
      str_replace($this->buildContext->getPath('source') . DIRECTORY_SEPARATOR , '',
        str_replace($this->sourceExtension, '', $path)));
  }

  private function buildPathFromSource(string $path) {
    //start with the build path
    //strip context from view name including first .
    //replace remaining dots in view name with slashes
    //add build extension
    return $this->buildContext->getPath('build') . DIRECTORY_SEPARATOR .
      str_replace('.', '/', str_replace($this->context . ".", '', $this->viewNameFromSource($path))) .
      $this->buildExtension;
  }
}
