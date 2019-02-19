<?php

namespace Glomr\Watch;

use Glomr\Watch\WatchStrategyInterface;
use Glomr\Watch\WatchAbstractClass;

class PollWatcher extends WatchAbstractClass {
  private $buildContext;
  private $lastBuildTime = 0;
  private $interval = 500;

  public function __construct(\Glomr\Build\BuildContext $buildContext, $options = []) {
    $this->buildContext = $buildContext;
    if (isset($options['interval'])) $this->interval = $options['interval'];
  }

  public function watch(){
    while(true){
      $build = false;
      foreach($this->buildContext->fetchSourceFiles() as $file){
        if($this->buildContext->mtime($file) > $this->lastBuildTime){
          $this->lastBuildTime = filemtime($file);
          $build = true;
        }
      }
      if($build) return true;
      else usleep($this->interval * 1000);
    }
  }
}
