<?php
namespace Electro\ViewEngine\Lib;

use Electro\Caching\Lib\CachingFileCompiler;
use Electro\Caching\Lib\CompositeCache;
use Electro\Caching\Lib\FileSystemCache;
use Electro\Caching\Lib\MemoryCache;
use Electro\Plugins\Matisse\Components\Internal\Text;

/**
 * A cache for view templates.
 */
class TemplateCache extends CachingFileCompiler
{
  public function __construct (FileSystemCache $fsCache)
  {
    $fsCache->setNamespace ('views/templates');
    $fsCache->setOptions ([
      'serializer' => function ($str) {
        return str_replace (Text::class, 'TEXT', serialize ($str));
      },
    ]);
    parent::__construct (new CompositeCache([new MemoryCache, $fsCache]));
  }

}
