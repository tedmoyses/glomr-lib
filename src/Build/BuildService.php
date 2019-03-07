<?php

namespace Glomr\Build;

use Glomr\Server\PhpServer;
use Glomr\Watch\InotifyEventsWatcher;
use Glomr\Watch\PollWatcher;
use Glomr\Log\Logr;

class BuildService {
  private $server = null;
  private $builders = [];
  private $watcher;

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
      Logr::warn("Watch out for server process left running");
    }
    Logr::info("Starting build service");
  }

  public function registerBuilder(\Glomr\Build\BuilderInterface $builder) {
    $this->builders[] = $builder;
  }

  public function runServer(string $address = '0.0.0.0', int $port = 8080, string $root = './build', $script = "script.php"){
    if($this->server === null) $this->server = new PhpServer($address, $port, $root, $script);
  }

  public function watch(int $interval,  $usePoller = false, $vars = [] ){
    if($this->watcher === null){
      if(defined('IN_CLOSE_WRITE') && !$usePoller){
        $this->watcher = new InotifyEventsWatcher($this->buildContext, ['interval' => $interval]);
      } else {
        $this->watcher = new PollWatcher($this->buildContext, ['interval' => $interval]);
      }
    }
    Logr::info("Watching source files, press Ctrl + C to quit");
    while($this->watcher->watch()){
      $this->build($vars);
    }
  }

  public function build($buildArgs = []) :bool{
		$starttime = microtime(true);
		if($this->buildContext->getEnv() === 'production'){
			$this->buildContext->cleanBuildDir();
		}
    Logr::info("Starting build ...");
    $buildArgs['buildID'] = substr(sha1(microtime()), 0, 7);
    foreach($this->builders as $builder) {
      if(method_exists($builder, 'beforeBuild')) $builder->beforeBuild();
      $buildArgs = array_merge($buildArgs, $builder->build($buildArgs));
      if(method_exists($builder, 'afterBuild')) $builder->afterBuild();
    }

    $totaltime = round((microtime(true) - $starttime) * 1000, 3);
    Logr::info("Build finished (${totaltime})ms");
    return true;
  }
}
