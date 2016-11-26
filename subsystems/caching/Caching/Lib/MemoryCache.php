<?php
namespace Electro\Caching\Lib;

use Electro\Interfaces\Caching\CacheInterface;

class MemoryCache implements CacheInterface
{
  protected $data      = [];
  protected $namespace = '';
  protected $path      = '';
  /** @var callable */
  protected $serializer = 'serialize';
  /** @var callable */
  protected $unserializer = 'unserialize';

  function add ($key, $value)
  {
    if (!$this->has ($key)) {
      if (is_object ($value) && $value instanceof \Closure)
        $value = $value ();
      $serialize = $this->serializer;
      $value     = $serialize ($value);
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
    if (isset($v)) {
      $unserialize = $this->unserializer;
      return $unserialize ($v);
    }
    if (is_object ($value) && $value instanceof \Closure)
      $value = $value ();
    return isset($value) ? ($this->set ($key, $value) ? $value : null) : null;
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
    // This cache has no timestamping capability.
    return false;
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
    if (isset($value) && (!is_object ($value) || !$value instanceof \Closure)) {
      $serialize = $this->serializer;
      $value     = $serialize ($value);
      setAt ($this->data, $this->path ? "$this->path.$key" : $key, $value, true);
    }
    else return false;
    return true;
  }

  function setOptions (array $options)
  {
    $this->serializer   = get ($options, 'serializer') ?: $this->serializer;
    $this->unserializer = get ($options, 'unserializer') ?: $this->unserializer;
    assert (is_callable ($this->serializer), 'The serializer option must be a callable reference');
    assert (is_callable ($this->unserializer), 'The unserializer option must be a callable reference');
  }

  function with (array $options)
  {
    // no op
  }
}
