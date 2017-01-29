<?php
namespace Electro\ViewEngine\Lib;

use Electro\Caching\Drivers\CompositeCache;
use Electro\Caching\Lib\CachingFileCompiler;

/**
 * A cache for view templates.
 */
class TemplateCache extends CachingFileCompiler
{
  public function __construct (\Electro\Caching\Drivers\FileSystemCache $fsCache, $enabled = true, $autoSync = true)
  {
    $fsCache->setNamespace ('views/templates');
    parent::__construct (new CompositeCache([new \Electro\Caching\Drivers\MemoryCache, $fsCache]), $enabled, $autoSync);
  }

}
