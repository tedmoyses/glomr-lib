<?php

namespace Glomr\Server;

use Symfony\Component\Process\Process;

class PhpServer {
  private $process;
  private $port = 8080;
  private $address = '0.0.0.0';
  private $path = 'build';

  public function __construct(){
    $this->process = new Process($this->getPhpCommand());
    $this->process->start();
  }

  private function getPhpCommand(){
    $address = getEnv('SERVER_ADDRESS')? genEnv('SERVER_ADDRESS') : $this->address;
    $port = getEnv('SERVER_PORT')? genEnv('SERVER_PORT') : $this->port;
    $path = getEnv('SERVER_PATH')? genEnv('SERVER_PATH') : $this->path;
    return "php -S ${address}:${port} -t ${path} serve.php";
    // return "php -S ${address}:${port} serve.php";
  }

  public function __destruct(){
    $this->process->stop();
  }
}
