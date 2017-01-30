<?php
namespace Electro\ViewEngine\Lib;

use Electro\Caching\Drivers\CompositeCache;
use Electro\Caching\Drivers\FileSystemCache;
use Electro\Caching\Drivers\MemoryCache;
use Electro\Caching\Lib\CachingFileCompiler;

/**
 * A cache for view templates.
 */
class TemplateCache extends CachingFileCompiler
{
  public function __construct (FileSystemCache $fsCache, $enabled = true, $autoSync = true)
  {
    $fsCache->setNamespace ('views/templates');
    parent::__construct (new CompositeCache([new MemoryCache, $fsCache]), $enabled, $autoSync);
  }

}
