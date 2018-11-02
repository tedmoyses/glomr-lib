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

  public function __construct(\Glomr\Build\BuildContext $buildContext, array $options = []) {
    $this->buildContext = $buildContext;
    $this->makeBlade();
    isset($options['context'])? $this->setContext($options['context']) : $this->setContext($this->context);
  }

  private function makeBlade(){
    $this->blade = new Blade(
      $this->buildContext->getPath('source'),
      $this->buildContext->getCachePath('blade')
    );

    $this->blade->compiler()->directive('style', function($expression){
      return '<link rel="stylesheet" href="' . htmlentities($expression) .  '" />';
    });

    $this->blade->compiler()->directive('script', function($expression){
      return '<script type="text/javascript" src="' . htmlentities($expression) .  '" /></script>';
    });

    $this->blade->compiler()->directive('assets', function($expression){
      $output = '<?php
        $assets = array_map(function ($asset){
          switch(pathinfo($asset, PATHINFO_EXTENSION)){
            case "js":
              return \'<script type="text/javascript" src="\' . htmlentities($asset) .  \'" /></script>\';
            case "css":
              return \'<link rel="stylesheet" href="\' . htmlentities($asset) . \'" />\';
            default:
              return \'\';
          }
        }, (array) '. $expression . ');
        echo implode("\n", $assets);
      ?>';
      return $output;
    });
  }

  private function setContext(string $context) {
    $this->sourcePath = $this->buildContext->getPath('source') . '/' . $context;
    if(!file_exists($this->sourcePath) && !is_dir($this->sourcePath)){
      throw new \RuntimeException("Context {$context} does not exist in " . $this->buildContext->getPath('source'));
    }
    $this->context = $context;
  }

  public function beforeBuild(){
    return;
  }

  public function build(array $buildArgs = []) {

    foreach($this->buildContext->fetchSourceFiles($this->context, $this->regex) as $viewTemplate){
      $viewName = $this->viewNameFromSource($viewTemplate);
      $destination = $this->buildPathFromSource($viewTemplate);

      /*
      if (!file_exists(dirname($destination))) {
        mkdir(dirname($destination), 0777, true);
      }
      */
      try {
        //file_put_contents($destination, $this->blade->make($viewName, $buildArgs));
      } catch (\InvalidArgumentException $e ) {
        Logr::log(Logr::Error, "Cannot find view! Name = ${$viewName}, path ${destination}\n");
      }
    }
    return $buildArgs;
  }

  public function afterBuild(){
    return;
  }

  /**
   * strip sourceExtention
   * Strip Source paths
   * replace / with .
   * @param  string $path path to a file in our source context
   * @return string       converted view name
   */
  private function viewNameFromSource(string $path) :string {
    return str_replace('/', '.',
      str_replace($this->sourcePath . '/' , '',
        str_replace($this->sourceExtension, '', $path)));
  }

  /**
   * start with the build path
   * strip context from view name including first .
   * replace remaining dots in view name with slashes
   * add build extension
   * @param  string $path Source file path
   * @return string       destination path
   */
  private function buildPathFromSource(string $path) :string {
    return $this->buildContext->getPath('build') . '/' .
      str_replace('.', '/', str_replace($this->context . ".", '', $this->viewNameFromSource($path))) .
      $this->buildExtension;
  }
}
