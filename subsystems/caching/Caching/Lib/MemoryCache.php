<?php
namespace Electro\Caching\Lib;

use Electro\Interfaces\Caching\CacheInterface;

class MemoryCache implements CacheInterface
{
  protected $data      = [];
  protected $namespace = '';
  protected $path      = '';

  function add ($key, $value)
  {
    if (!$this->has ($key)) {
      if (is_object ($value) && $value instanceof \Closure)
        $value = $value ();
      return $this->set ($key, $value);
    }
    return false;
  }

  function clear ()
  {
    unsetAt ($this->data, $this->path);
    return true;
  }

  function get ($key, $value = null)
  {
    $v = getAt ($this->data, $this->path ? "$this->path.$key" : $key);
    if (isset($v))
      return $v;
    if (is_object ($value) && $value instanceof \Closure)
      $value = $value ();
    return $this->set ($key, $value) ? $value : null;
  }

  function getNamespace ()
  {
    return $this->namespace;
  }

  function setNamespace ($name)
  {
    $this->namespace = $name;
    $this->path      = str_replace ('/', '.', $name);
  }

  function getTimestamp ($key)
  {
    return time ();
  }

  function has ($key)
  {
    return getAt ($this->data, $this->path ? "$this->path.$key" : $key) != null;
  }

  function inc ($key, $value = 1)
  {
    $v = $this->get ($key);
    if (!is_numeric ($v))
      return false;
    $this->set ($key, $v + $value);
    return true;
  }

  function prune ()
  {
    // no op
  }

  function remove ($key)
  {
    unsetAt ($this->data, $this->path ? "$this->path.$key" : $key);
    return true;
  }

  function set ($key, $value)
  {
    if (isset($value) && (!is_object ($value) || !$value instanceof \Closure))
      setAt ($this->data, $this->path ? "$this->path.$key" : $key, $value, true);
    else return false;
    return true;
  }

  function setOptions (array $options)
  {
    // no op
  }

  function with (array $options)
  {
    // no op
  }
}
