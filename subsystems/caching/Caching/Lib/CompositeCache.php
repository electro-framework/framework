<?php
namespace Electro\Caching\Lib;

use Electro\Interfaces\Caching\CacheInterface;

class CompositeCache implements CacheInterface
{
  /**
   * @var CacheInterface[]
   */
  private $caches;

  /**
   * CompositeCache constructor.
   *
   * @param CacheInterface[] $caches A list of cache instances in order of cascading.
   */
  public function __construct (array $caches)
  {
    $this->caches = $caches;
  }

  function add ($key, $value)
  {
    foreach ($this->caches as $cache) {
      if (!$cache->add ($key, $value))
        return false;
    }
    return true;
  }

  function clear ()
  {
    $i = count ($this->caches);
    // We must iterate in reverso order, otherwise reading concurrently would undo the work being done here.
    while ($i--)
      $this->caches[$i]->clear ();
  }

  function get ($key, $value = null)
  {
    foreach ($this->caches as $i => $cache) {
      if (!is_null ($v = $cache->get ($key))) {
        while ($i--)
          $this->caches[$i]->set ($key, $v);
        return $v;
      }
    }
    if (is_object ($value) && $value instanceof \Closure)
      $value = $value ();
    return $this->set ($key, $value) ? $value : null;
  }

  function getNamespace ()
  {
    return '';
  }

  function getTimestamp ($key)
  {
    foreach ($this->caches as $cache)
      if ($v = $cache->getTimestamp ($key))
        return $v;
    return false;
  }

  function has ($key)
  {
    foreach ($this->caches as $cache)
      if ($cache->has ($key))
        return true;
    return false;
  }

  function inc ($key, $value = 1)
  {
    foreach ($this->caches as $i => $cache)
      // Search for a value on all the caches
      if ($cache->has ($key)) {
        // If a value is found, increment it
        if ($cache->inc ($key, $value)) {
          // Get the value to populate the caches where it was not found
          $v = $cache->get ($key);
          if (isset($v)) {
            while ($i--)
              if (!$this->caches[$i]->add ($key, $v))
                break; // abort if, meanwhile, someone has already populated the caches by writing a new value
            return true;
          }
          // else someone has already deleted it
        }
      }
    return false;
  }

  function prune ()
  {
    foreach ($this->caches as $cache)
      $cache->prune ();
  }

  function remove ($key)
  {
    $o = true;
    $i = count ($this->caches);
    // We must iterate in reverso order, otherwise reading the key concurrently would undo the work being done here.
    while ($i--)
      $o = $o && $this->caches[$i]->remove ($key);
    return $o;
  }

  function set ($key, $value)
  {
    $o = true;
    foreach ($this->caches as $cache)
      $o = $o && $cache->set ($key, $value);
    return $o;
  }

  function setNamespace ($name)
  {
    // not valid
  }

  function setOptions (array $options)
  {
    // no op (options differ between caches, so you must set them individually on each one)
  }

  function with (array $options)
  {
    // no op
  }

}
