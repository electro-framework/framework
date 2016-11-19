<?php
namespace Electro\Caching\Lib;

use Electro\Interfaces\Caching\CacheInterface;

class CompiledFileCache implements CacheInterface
{
  /**
   * @var CacheInterface The underlying cache instance.
   */
  protected $cache;

  public function __construct (CacheInterface $underlyingCache) {
    $this->cache = $underlyingCache;
  }

  function add ($key, $value)
  {

  }

  function clear ()
  {

  }

  function delete ($key)
  {

  }

  function get ($key, $value)
  {
    $t = @filemtime ($key);
    if ($t) {
      
    }
  }

  function getNamespace ()
  {

  }

  function has ($key)
  {

  }

  function inc ($key, $value = 1)
  {

  }

  function prune ()
  {

  }

  function set ($key, $value)
  {

  }

  function setNamespace ($name)
  {

  }

  function setOptions (array $options)
  {

  }

  function with (array $options)
  {

  }
}
