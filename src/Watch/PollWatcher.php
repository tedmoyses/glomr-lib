<?php

namespace Glomr\Watch;

use Glomr\Watch\WatchStrategyInterface;

class PollWatcher implements WatchStrategyInterface {
  private $buildContext;
  private $lastBuildTime = 0;
  private $interval;

  public function __construct(\Glomr\Build\BuildContext $buildContext, int $interval = 500) {
    $this->buildContext = $buildContext;
    $this->interval = $interval;  
  }

  public function watch(){
    while(true){
      $build = false;
      foreach($this->buildContext->fetchSourceFiles() as $file){
        if(filemtime($file) > $this->lastBuildTime){
          $this->lastBuildTime = filemtime($file);
          $build = true;
        }
      }
      if($build) return true;
      else usleep($this->interval * 1000);
    }
  }
}
