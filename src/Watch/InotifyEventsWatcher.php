<?php

namespace Glomr\Watch;

use Glomr\Watch\WatchStrategyInterface;
use Glomr\Log\Logr;

class InotifyEventsWatcher implements WatchStrategyInterface {
  private $watchDirectories = [];
  private $watchResource;
  const watchEvents = IN_CLOSE_WRITE | IN_CREATE | IN_DELETE_SELF;
  private $buildContext;
  private $interval;

  public function __construct(\Glomr\Build\BuildContext $buildContext, int $interval = 500){
    $this->buildContext = $buildContext;
    if(!function_exists('inotify_init')){
      throw new \Exception('Inotify not installed, use Poller or other watch strategy');
    }
    $this->watchResource = inotify_init($this->buildContext->getPath('source'));
    stream_set_blocking($this->watchResource, 0);
    $this->addWatchDirectory($this->buildContext->getPath('source'));
    foreach($this->buildContext->fetchSourceDirectories() as $dir) {
      $this->addWatchDirectory($dir);
    }
    $this->interval = $interval;
  }

  public function watch(){
    while(true) {
      $events = $this->getEvents();
      if($events !== false) Logr::debug("Inotify watch events", $events);
      if(is_array($events) && count($events) > 0) return true;
      usleep($this->interval * 1000);
    }
  }

  private function addWatchDirectory(string $path){
    $watch = inotify_add_watch($this->watchResource, $path, self::watchEvents);
    if($watch === false){
      Logr::warn("Failed to add watch for $path");
      return;
    }
    $this->watchDirectories[] = [
      'watch' => $watch,
      'path' => $path
    ];
    Logr::debug("Watch (${watch})added for ${path}\n");
  }

  private function removeWatchDirectory($id){
    $watch = $this->getWatchById($id);
    if($watch !== null){
      @inotify_rm_watch($this->watchResource, $watch['watch']);
      unset($this->watchDirectories[array_search($watch, $this->watchDirectories)]);
      Logr::debug("Watch removed for ${watch['path']}". PHP_EOL);
    } else {
      throw new \Exception("Error could not find or remove watch (${id})");
    }
  }

  private function getEvents() {
    if (count($this->watchDirectories) === 0) $this->watchBuild();
    $events = inotify_read($this->watchResource);
    if(is_array($events) && count($events) > 0){
      foreach($events as $event){
        if($event['mask'] & IN_CREATE && $event['mask'] & IN_ISDIR) {
          //we have new directory to watch
          //we need to find the parent directory to build the path
          $parent = $this->getWatchById($event['wd']);
          $this->addWatchDirectory($parent['path'] . DIRECTORY_SEPARATOR . $event['name']);
        } elseif($event['mask'] & IN_DELETE_SELF) {
          $this->removeWatchDirectory($event['wd']);
        }
      }
    }
    return $events;
  }

  private function getWatchById($id){
    foreach($this->watchDirectories as $watch){
      if ($watch['watch'] == $id) return $watch;
    }
    return null;
  }

  public function __destruct(){
    Logr::info("removing watches");
    foreach($this->watchDirectories as $watch){
      $this->removeWatchDirectory($watch['watch']);
    }
  }
}
