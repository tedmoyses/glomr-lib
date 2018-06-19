<?php

namespace Glomr\Log;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use \Bramus\Monolog\Formatter\ColoredLineFormatter;

class Logr {

  private static $loggerInstance = null;
  private static $errorInstance = null;
  private static $channel = 'Glomr';
  private static $debug = false;

  public static function error($message, $arr1 = [], $arr2 = []){
    if(self::$errorInstance === null){
      self::$errorInstance = self::makeInstance(Logger::ERROR);
    }
    self::$errorInstance->log(Logger::ERROR, $message, $arr1, $arr2);
  }

  public static function debug($message, $arr1 = [], $arr2 = []){
    if(self::$errorInstance === null){
      self::$errorInstance = self::makeInstance(Logger::DEBUG);
    }
    self::$errorInstance->log(Logger::DEBUG, $message, $arr1, $arr2);
  }

  public static function info($message, $arr1 = [], $arr2 = []){
    if(self::$loggerInstance === null){
      self::$loggerInstance = self::makeInstance(Logger::INFO);
    }
    self::$loggerInstance->log(Logger::INFO, $message, $arr1, $arr2);
  }

  public static function warn($message, $arr1 = [], $arr2 = []){
    if(self::$loggerInstance === null){
      self::$loggerInstance = self::makeInstance(Logger::WARNING);
    }
    self::$loggerInstance->log(Logger::WARNING, $message, $arr1, $arr2);
  }

  public static function setDebug($debug = false){
    if($debug === false) self::$debug = false;
    else self::$debug = true;
  }

  private static function makeInstance($level){
    if ( defined('PHPUNIT_COMPOSER_INSTALL') || defined('__PHPUNIT_PHAR__')) {
      $stream = '/dev/null';
    } elseif($level >= Logger::ERROR) {
      $stream = 'php://stderr';
    } else {
      $stream = 'php://stdout';
    }
    $instance = new Logger(self::$channel);
    $handler = new StreamHandler($stream, self::$debug ? Logger::DEBUG : Logger::INFO);
    $handler->setFormatter(new ColoredLineFormatter(null, "%message% %context% %extra%\n", null, false, true));
    $instance->pushHandler($handler);
    return $instance;
  }

}
