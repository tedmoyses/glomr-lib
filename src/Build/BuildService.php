<?php

namespace Glomr\Build;

use Glomr\Server\PhpServer;
use Glomr\Log\Logr;

class BuildService {
  private $server = null;
  private $builders = [];

  public function __construct(\Glomr\Build\BuildContext $buildContext){
    $this->buildContext = $buildContext;
    if(function_exists('pcntl_async_signals') && function_exists('pcntl_signal')){
      pcntl_async_signals(true);
      pcntl_signal(SIGINT, function ($sig, $siginfo) {
        Logr::info("Attempting shutdown...");
        exit;
      });
    } else {
      Logr::warn("Cannot add signal handler for shutdown");
      Logr::warn("Watch for server process left running");
    }
    Logr::info("Starting build service");
  }

  public function registerBuilder(\Glomr\Build\BuilderInterface $builder) {
    $this->builders[] = $builder;
  }

  public function runServer(string $address = '0.0.0.0', int $port = 8080, string $root = './build'){
    if($this->server === null) $this->server = new PhpServer($address, $port, $root);
  }

  public function watch(int $interval,  $usePoller = false ){
    if($this->watcher === null){
      if(defined(IN_CLOSE_WRITE) && !$usePoller){
        $this->watcher = new InotifyEventsWatcher($this->buildContext, $interval);
      } else {
        $this->watcher = new PollWatcher($this->buildContext, $interval);
      }
    }
    Logr::info("Watching source files, press Ctrl + C to quit");
    while($this->watcher->watch()){
      $this->build();
    }
  }

  public function build(){
    $starttime = microtime(true);
    Logr::info("Starting build ...");
    $buildID = substr(sha1(microtime()), 0, 7);
    foreach($this->builders as $builder) {
      if(method_exists($builder, 'beforeBuild')) $builder->beforeBuild();
      $builder->build(['buildID' => $buildID]);
      if(method_exists($builder, 'afterBuild')) $builder->afterBuild();
    }

    $totaltime = round((microtime(true) - $starttime) * 1000, 3);
    Logr::info("Build finished (${totaltime})ms");
    return true;
  }
}