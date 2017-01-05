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
 * ><p>The `/` or `\` characters on keys will be replaced by `.` to generate a value file name.
 * This means you can't use keys to reference directories under the cache directory. For that purpose, use
 * {@see setNamespace}.
 *
 * <p>You may define which serializer and unserializer functions will be used when saving and loading data.
 * By default, the {@see serialize} and {@see unserialize} functions are used. You can set these via the `serializer`
 * and `unserializer` options.
 *
 * <p>You may enable the `dataIsCode` option (set it to TRUE) if you are saving and retrieving PHP source code.
 * On this mode, the cache will load and execute files via PHP's `include`, and it will be able to take advantage of
 * PHP's opcode cache.
 *
 * ##### Not shared
 * Injecting instances of this class will yield different instances each time.
 */
class FileSystemCache implements CacheInterface
{
  /** @var string The root path plus the current namespace. */
  protected $basePath = '';
  /** @var bool */
  protected $dataIsCode = false;
  /** @var string */
  protected $namespace = '';
  /** @var string */
  protected $rootPath = '';
  /** @var callable */
  protected $serializer = 'serialize';
  /** @var callable */
  protected $unserializer = 'unserialize';
  /** @var string */
  private $appDir;
  /** @var LoggerInterface */
  private $logger;

  public function __construct (KernelSettings $kernelSettings, CachingSettings $cachingSettings,
                               LoggerInterface $logger)
  {
    $this->rootPath =
    $this->basePath = "$kernelSettings->baseDirectory/$kernelSettings->storagePath/$cachingSettings->cachePath";
    $this->logger   = $logger;
    $this->appDir   = dirname ("$kernelSettings->baseDirectory/$kernelSettings->modulesPath") . '/';
  }

  function add ($key, $value)
  {
    $path = $this->toFileName ($key);
    $f    = file_exists ($path) ? null : @fopen ($path, 'x'); // mode 'x' returns NULL if file exists
    if (!$f) {
      // Maybe the directory is nonexistent...
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
      if (!$this->dataIsCode) {
        $serialize = $this->serializer;
        $value     = $serialize ($value);
      }
      fwrite ($f, $value);
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

  function get ($key, $value = null)
  {
    $path = $this->toFileName ($key);
    if ($this->dataIsCode) {
      // Slight possibility of reading truncated data here
      if (file_exists ($path))
        return include $path;
    }
    else {
      $path = $this->toFileName ($key);
      $f    = file_exists ($path) ? @fopen ($path, 'r') : null;
      if ($f) {
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
    }
    // The file couldn't be read.
    // Maybe the directory is nonexistent...
    if ($this->createDirIfAbsent ())
      // Reattempt the operation now that we have a valid directory.
      return $this->get ($key, $value);
    // Ok, so the directory is valid. Let's check if the file exists but is inaccessible for reading.
    if (file_exists ($path) && !is_readable ($path))
      return $this->error ("File $path exists but its not readable");
    // Well, the file doesn't exist yet; therefore, create a new cache entry.
    // Note: if by chance it has been created meanwhile, well, too bad, just overwrite it.
    // First, compute the value to be saved, if required.
    if (is_object ($value) && $value instanceof \Closure)
      $value = $value ();
    // Save the value and return it, or NULL if that failed or there's no value to be saved.
    if (isset($value)) {
      $v = $this->set ($key, $value) ? $value : null;
      if (isset($v))
        return $this->dataIsCode ? include $path : $v;
    }
    return null;
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
    $f = $this->toFileName ($key);
    return file_exists ($f) ? @filemtime ($f) : 0;
  }

  function has ($key)
  {
    return file_exists ($this->toFileName ($key));
  }

  function inc ($path, $value = 1)
  {
    // not available (wouldn't make sense with this kind of cache; it's too slow for implementing atomic counters)
    return false;
  }

  function prune ()
  {
    // not applicable
  }

  function remove ($key)
  {
    $path = $this->toFileName ($key);
    return @unlink ($path);
  }

  function set ($key, $value)
  {
    if (is_null ($value))
      return true;
    if (is_object ($value) && $value instanceof \Closure)
      return false;
    $path = $this->toFileName ($key);
    $f = @fopen ($path, 'w');
    if (!$f) {
      // Maybe the directory is nonexistent...
      if ($this->createDirIfAbsent ())
        // Reattempt the operation now that we have a valid directory.
        return $this->set ($key, $value);
      // Nope, we really can't open the file. It may exist and we haven't permission for writing to it or the
      // directory may be invalid, or something else.
      if (file_exists ($path) && !is_writable ($path))
        return $this->error ("File $path exists but is not writable");
      return $this->error ("Can't open $path for writing");
    }
    try {
      if (!flock ($f, LOCK_EX)) // Block if file already locked. Then proceed to override its contents.
        return false; // Abort if the filesystem doesn't support locking.
      if (!$this->dataIsCode) {
        $serialize = $this->serializer;
        $value     = $serialize ($value);
      }
      return fwrite ($f, $value) !== false;
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
    $this->dataIsCode   = (bool)get ($options, 'dataIsCode') ?: $this->dataIsCode;
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
    else return false;
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
    if (str_beginsWith ($key, $this->appDir))
      $key = substr ($key, strlen ($this->appDir));
    return "$this->basePath/" . preg_replace ('#/|\\\#', '.', escapeshellcmd ($key));
  }
}
