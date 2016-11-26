<?php
namespace Electro\ViewEngine\Lib;

use Electro\Caching\Lib\CachingFileCompiler;
use Electro\Caching\Lib\CompositeCache;
use Electro\Caching\Lib\FileSystemCache;
use Electro\Caching\Lib\MemoryCache;

/**
 * A cache for view templates.
 */
class TemplateCache extends CachingFileCompiler
{
  public function __construct (FileSystemCache $fsCache)
  {
    $fsCache->setNamespace ('views/templates');
    parent::__construct (new CompositeCache([new MemoryCache, $fsCache]));
  }

}
