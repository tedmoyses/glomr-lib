<?php

namespace Glomr\Build\Filter;

use Assetic\Filter\FilterInterface;
use Assetic\Asset\AssetInterface;
use MatthiasMullie\Minify;

class CssMinifyFilter implements FilterInterface {
  public function filterLoad(AssetInterface $asset){
  }

  public function filterDump(AssetInterface $asset){
    $cssmin = new Minify\CSS($asset->getContent());
    $asset->setContent($cssmin->minify());
  }
}
