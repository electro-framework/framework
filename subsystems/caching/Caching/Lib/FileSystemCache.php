<?php
namespace Electro\Caching\Lib;

use Electro\Caching\Config\CachingSettings;
use Electro\Interfaces\Caching\CacheInterface;
use Electro\Kernel\Config\KernelSettings;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

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
  /** @var LoggerInterface */
  private $logger;

  public function __construct (KernelSettings $kernelSettings, CachingSettings $cachingSettings,
                               LoggerInterface $logger)
  {
    $this->rootPath = $this->basePath = "$kernelSettings->baseDirectory/$cachingSettings->cachePath";
    $this->logger   = $logger;
  }

  function add ($key, $value)
  {
    $path = $this->toFileName ($key);
    $f    = @fopen ($path, 'x');
    if (!$f) {
      // Maybe the directory is inexistent...
      if (!$this->createDirIfAbsent ()) {
        // Nope, the file probably exists already.
        if (file_exists ($path))
          // Yep, it exists, so this method should do nothing.
          return false;
        // Alas, the file is inaccessible.
        return $this->error ("Can't open $path for writing");
      }
      // Reattempt the operation now that we have a valid directory.
      return $this->add ($key, $value);
    }
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
    $tmp = sys_get_temp_dir () . '/' . str_random (8);
    // Ensure an instaneous, atomic removal.
    rename ($this->basePath, $tmp);
    // Try to garbage-collect as much as possible of the directory's contents (some deletions may fail due to files
    // being locked).
    if (@rrmdir ($tmp))
      $this->error ("Couldn't cleanup the $tmp directory", LogLevel::NOTICE);
  }

  function delete ($key)
  {
    $path = $this->toFileName ($key);
    return @unlink ($path);
  }

  function get ($key, $value)
  {
    $path = $this->toFileName ($key);
    $f    = @fopen ($path, 'r');
    if (!$f) {
      // Maybe the directory is inexistent...
      if ($this->createDirIfAbsent ())
        // Reattempt the operation now that we have a valid directory.
        return $this->get ($key, $value);
      // Ok, so the directory is valid. Let's check if the file exists but is inaccessible for reading.
      if (file_exists ($path) || !is_readable ($path))
        return $this->error ("File $path exists but its not readable");
      // Well, the file doesn't exist yet; therefore, create a new cache entry.
      // Note: if by chance it has been created meanwhile, well, too bad, just overwrite it.
      // First, compute the value to be saved, if required.
      if (is_object ($value) && $value instanceof \Closure)
        $value = $value ();
      // Save the value and return it, or NULL if that failed.
      return $this->set ($path, $value) ? $value : null;
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
    // There's no need to make sure the directory exists; it will be done later, if an error occurs.
  }

  function getTimestamp ($key)
  {
    return @filemtime ($this->toFileName ($key));
  }

  function has ($key)
  {
    return file_exists ($this->toFileName ($key));
  }

  function inc ($path, $value = 1)
  {
    // not available (wouldn't make sense with this kind of cache; it's too slow for implementing atomic counters)
  }

  function prune ()
  {
    // not applicable
  }

  function set ($key, $value)
  {
    if (is_null ($value))
      // This is not allowed (you know why).
      return $this->error ("Can't cache a NULL value");
    $path = $this->toFileName ($key);
    $f    = @fopen ($path, 'w');
    if (!$f) {
      // Maybe the directory is inexistent...
      if ($this->createDirIfAbsent ())
        // Reattempt the operation now that we have a valid directory.
        return $this->set ($key, $value);
      // Nope, we really can't open the file. It may exist and we haven't permission for writing to it or the
      // directory may be invalid, or something else.
      if (file_exists ($path) && !is_writable ($path))
        return $this->error ("File $path exists but is not writable");
      return $this->error ("Can't open $path for writing");
    }
    if (!flock ($f, LOCK_EX)) // Block if file already locked. Then proceed to override its contents.
      return false; // Abort if the filesystem doesn't support locking.
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
    // not applicable
  }

  /**
   * Creates a directory at the current `basePath` if it doesn't exist yet.
   *
   * <p>A return value of TRUE means the previous file access operation should be reattempted.
   *
   * @return bool TRUE if the directory has been successfully created now, FALSE if it already existed or it couldnt be
   *              created.
   */
  private function createDirIfAbsent ()
  {
    $path = $this->basePath;
    if (!file_exists ($path)) {
      if (!@mkdir ($path, 0777, true))
        // Check if the directory was created meanwhile by a concurrent process.
        if (!file_exists ($path))
          // If it wasn't, it is not possible to create a directory at the given path.
          return $this->error ("Can't create directory $path");
    }
    return true;
  }

  /**
   * Caches should not throw exceptions, so errors are simply logged as warnings.
   *
   * @param string $message
   * @param string $level The kind of log message.
   * @return false
   */
  private function error ($message, $level = LogLevel::WARNING)
  {
    $this->logger->log ($level, "[cache] $message");
    return false;
  }

  private function toFileName ($key)
  {
    return "$this->basePath/" . str_replace ('/', '\\', escapeshellarg ($key));
  }
}
