<?php

namespace Glomr\Server;

use Symfony\Component\Process\Process;
use Glomr\Log\Logr;

class PhpServer {
  private $process;
  private $port;
  private $address;
  private $path;

  public function __construct(string $address = '0.0.0.0', int $port = 8080, string $path = './build', $script = ""){
    $this->setAddress($address);
    $this->port = $port;
    $this->setPath($path);
    if($script == ""){
      $script = __DIR__ . "/server.php";
    }
    $this->setScript($script);
    $this->process = new Process($this->getPhpCommand());
    $this->process->disableOutput();

    $this->process->start();
    Logr::info("Started PHP server on {$this->address}:{$this->port} serving {$this->path}");
  }

  public function setAddress($address){
    if($ip = filter_var($address, FILTER_VALIDATE_IP) !== false){
      $this->address = $address;
    } else {
      throw new \RuntimeException("Invalid IP bind address for server");
    }
  }

  public function setPath($path){
    if(is_dir($path)){
      $this->path = $path;
    } else {
      throw new \RuntimeException("Server root path does not exist");
    }
  }

  public function setScript($script){
    if($script == "" || is_file($script)){
      $this->script = $script;
    } else {
      throw new \RuntimeException("Server script does not exist");
    }
  }

  public function getPhpCommand(){
    return "php -S {$this->address}:{$this->port} -t {$this->path} {$this->script}";
  }

  public function getPid(){
    return $this->process->getPid();
  }

  public function stop(){
    $this->process->stop(0, SIGKILL);
  }

  public function __destruct(){
    $this->stop();
  }
}
