<?php

namespace Glomr\Build\Filter;

use Assetic\Filter\FilterInterface;
use Assetic\Asset\AssetInterface;
use MatthiasMullie\Minify;

class JsMinifyFilter implements FilterInterface {
  public function filterLoad(AssetInterface $asset){
  }

  public function filterDump(AssetInterface $asset){
    $jsmin = new Minify\JS($asset->getContent());
    $asset->setContent($jsmin->minify());
  }
}
