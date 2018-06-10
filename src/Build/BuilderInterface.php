<?php

namespace Glomr\Build;

interface BuilderInterface {
  public function build(array $buildArgs = []);
}
