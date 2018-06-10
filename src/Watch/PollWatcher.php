<?php

namespace Glomr\Watch;

use Glomr\Watch\WatchStrategyInterface;

class PollWatcher implements WatchStrategyInterface {
  private $buildContext;
  private $lastBuildTime = 0;
  private $interval = 500;

  public function __construct(\Glomr\Build\BuildContext $buildContext) {
    $this->buildContext = $buildContext;
    $this->interval = getenv('interval') ? getenv('interval') : $this->interval;
  }

  public function watchBuild(){
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
