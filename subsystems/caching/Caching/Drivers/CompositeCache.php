<?php

namespace Electro\Caching\Drivers;

use Electro\Interfaces\Caching\CacheInterface;

/**
 * A cache that composes several caches in a cascading arrangement.
 *
 * ##### Not shared
 * Injecting instances of this class will yield different instances each time.
 */
class CompositeCache implements CacheInterface
{
  /**
   * @var CacheInterface[]
   */
  private $caches;
  private $enabled = true;

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
    if (!$this->enabled)
      return true;
    foreach ($this->caches as $cache) {
      if (!$cache->add ($key, $value))
        return false;
    }
    return true;
  }

  function clear ()
  {
    if ($this->enabled) {
      $i = count ($this->caches);
      // We must iterate in revers order, otherwise reading concurrently would undo the work being done here.
      while ($i--)
        $this->caches[$i]->clear ();
    }
  }

  function enable ($enabled = true)
  {
    $this->enabled = $enabled;
  }

  function get ($key, $value = null)
  {
    if ($this->enabled) {
      // Search for a value on all the caches.
      foreach ($this->caches as $i => $cache) {
        // When a value is found...
        if (!is_null ($v =
          $cache->get ($key)) // Note: do NOT send the default value to get() as we do not want to compute it now if the get fails.
        ) {
          // Populate the previous caches with the value found.
          while ($i) {
            $this->caches[--$i]->set ($key, $v);
          }
          // Return the value.
          return $v;
        }
      }
    }
    // The item is not cached on any of the caches; compute the current value...
    if (is_object ($value) && $value instanceof \Closure)
      $value = $value ();
    // and store it on all caches.
    if (isset($value))
      $this->set ($key, $value);
    return $value;
  }

  function getNamespace ()
  {
    return '';
  }

  function getTimestamp ($key)
  {
    if (!$this->enabled)
      return 0;
    $o = false;
    foreach ($this->caches as $cache)
      if ($v = $cache->getTimestamp ($key))
        return $v;
      else if ($v === 0)
        $o = $v;
    // It returns FALSE only if all caches return FALSE, 0 othwerwise.
    return $o;
  }

  function has ($key)
  {
    if ($this->enabled)
    foreach ($this->caches as $cache)
      if ($cache->has ($key))
        return true;
    return false;
  }

  function inc ($key, $value = 1)
  {
    if ($this->enabled)
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

  function isEnabled ()
  {
    return $this->enabled;
  }

  function prune ()
  {
    if ($this->enabled)
    foreach ($this->caches as $cache)
      $cache->prune ();
  }

  function remove ($key)
  {
    if (!$this->enabled)
      return true;
    $o = true;
    $i = count ($this->caches);
    // We must iterate in reverso order, otherwise reading the key concurrently would undo the work being done here.
    while ($i--)
      $o = $this->caches[$i]->remove ($key) && $o;
    return $o;
  }

  function set ($key, $value)
  {
    if (!$this->enabled)
      return true;
    $o = true;
    // Store the value on all caches.
    foreach ($this->caches as $cache)
      $o = $cache->set ($key, $value) && $o;
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
