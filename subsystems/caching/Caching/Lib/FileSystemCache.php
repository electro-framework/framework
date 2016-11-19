<?php
namespace Electro\Caching\Lib;

use Electro\Caching\Config\CachingSettings;
use Electro\Interfaces\Caching\CacheInterface;
use Electro\Kernel\Config\KernelSettings;
use PhpKit\Flow\FilesystemFlow;

/**
 * A cache that stores each item as a file on a filesystem. The item key determines the file name. The item value is
 * stored under a serialized form.
 *
 * ><p>The `/` character on keys will be replaced by `\` to generate a value file name. This means you can't use keys
 * to reference directories under the cache directory.
 *
 * <p>You may define which serializer and unserializer functions will be used when saving and loading data.
 * By default, the {@see serialize} and {@see unserialize} functions are used.
 */
class FileSystemCache implements CacheInterface
{
  /** @var string The root path plus the current namespace. */
  protected $basePath = '';
  /** @var string */
  protected $namespace = '';
  /** @var string */
  protected $rootPath = '';
  /** @var callable */
  protected $serializer = 'serialize';
  /** @var callable */
  protected $unserializer = 'unserialize';

  public function __construct (KernelSettings $kernelSettings, CachingSettings $cachingSettings)
  {
    $this->rootPath = $this->basePath = "$kernelSettings->baseDirectory/$cachingSettings->cachePath";
  }

  function add ($key, $value)
  {
    $key = str_replace ('/', '\\', $key);
    $f   = @fopen ("$this->basePath/$key", 'x');
    if (!$f)
      return false;
    try {
      if (!flock ($f, LOCK_EX | LOCK_NB)) // Don't block if file already locked
        return false; // Abort if the filesystem doesn't support locking.
      if (is_null ($value))
        throw new \RuntimeException("Can't cache a NULL value");
      if (is_object ($value) && $value instanceof \Closure)
        $value = $value ();
      $serialize = $this->serializer;
      fwrite ($f, $serialize ($value));
      fflush ($f);
      return true;
    }
    finally {
      flock ($f, LOCK_UN);
      fclose ($f);
    }
  }

  function clear ()
  {
    FilesystemFlow::from ($this->basePath)->onlyFiles ()->each (function ($filename) {
      @unlink ($filename);
    });
  }

  function delete ($key)
  {
    $key = str_replace ('/', '\\', $key);
    return @unlink ("$this->basePath/$key");
  }

  function get ($key, $value)
  {
    $key = str_replace ('/', '\\', $key);
    $f   = @fopen ("$this->basePath/$key", 'r');
    if (!$f) {
      if (is_null ($value))
        throw new \RuntimeException("Can't cache a NULL value");
      if (is_object ($value) && $value instanceof \Closure)
        $value = $value ();
      return $this->set ($key, $value) ? $value : null;
    }
    try {
      if (!flock ($f, LOCK_SH)) // Block if file already locked.
        return false; // Abort if the filesystem doesn't support locking.
      $unserialize = $this->unserializer;
      return $unserialize (stream_get_contents ($f));
    }
    finally {
      flock ($f, LOCK_UN);
      fclose ($f);
    }
  }

  function getNamespace ()
  {
    return $this->namespace;
  }

  function setNamespace ($name)
  {
    $this->namespace = $name;
    $this->basePath  = $name ? "$this->rootPath/$name" : $this->rootPath;
  }

  function has ($key)
  {
    $key = str_replace ('/', '\\', $key);
    return file_exists ("$this->basePath/$key");
  }

  function inc ($key, $value = 1)
  {
    // no op
  }

  function prune ()
  {
    // no op
  }

  function set ($key, $value)
  {
    $key = str_replace ('/', '\\', $key);
    $f   = @fopen ("$this->basePath/$key", 'w');
    if (!$f)
      return false;
    if (!flock ($f, LOCK_EX)) // Block if file already locked. Then proceed to override its contents.
      return false; // Abort if the filesystem doesn't support locking.
    if (is_null ($value))
      throw new \RuntimeException("Can't cache a NULL value");
    if (is_object ($value) && $value instanceof \Closure)
      $value = $value ();
    try {
      $serialize = $this->serializer;
      return fwrite ($f, $serialize ($value));
    }
    finally {
      flock ($f, LOCK_UN);
      fclose ($f);
    }
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
