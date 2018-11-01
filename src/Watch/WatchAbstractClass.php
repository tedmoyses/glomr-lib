<?php

namespace Glomr\Watch;

use Glomr\Build\BuildContext;

abstract class WatchAbstractClass implements WatchStrategyInterface{
  public function __construct(\Glomr\Watch\BuildContext $buildContext, array $options = []){

  }
}
