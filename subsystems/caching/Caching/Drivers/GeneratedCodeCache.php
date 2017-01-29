<?php

namespace Electro\Caching\Drivers;

use Electro\Caching\Config\CachingSettings;
use Electro\Interfaces\Caching\CacheInterface;
use Electro\Kernel\Config\KernelSettings;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * A cache where each item is a PHP class/interface/trait definition.
 *
 * <p>Items are stored as files so that they may be accelerated by PHP's OPcache.
 * The item key should be the fully qualified class/interface/trait name and it determines the file name.
 * The item value is stored as raw PHP source code.
 *
 * <p>Reading an item will return `true` if the item exists and `false` if it does not and no default value is given.
 *
 * <p>There is no need to put a memory cache in front of this cache; reading an item will execute it only once per
 * program execution; subsequent reads will have no effect, even if the item was removed from the cache.
 *
 * <p>Writing an item will not execute it immediately; unless it was done via the `$default` argument of {@see get}.
 *
 * <p>Only string values are accepted for writing.
 *
 * <p>You can use the `caching` option to disable the saving of code to disk, by setting it to `false` (it defaults to
 * `true`); the cache will behave the same, only saving is disabled.
 *
 * ><p>The `/` or `\` characters on keys will be replaced by `.` to generate a value file name.
 * This means you can't use keys to reference directories under the cache directory. For that purpose, use
 * {@see setNamespace}.
 *
 * ##### Important
 * `add`, `inc`, `prune`, `setOptions` and `with` do nothing on this cache.
 *
 * ##### Not shared
 * Injecting instances of this class will yield different instances each time.
 */
class GeneratedCodeCache implements CacheInterface
{
  /** @var string The root path plus the current namespace. */
  protected $basePath = '';
  /** @var string */
  protected $namespace = '';
  /** @var string */
  protected $rootPath = '';
  /** @var string */
  private $appDir;
  /** @var bool When FALSE noting will be saved to disk */
  protected $enabled = true;
  /** @var LoggerInterface */
  private $logger;
  /** @var array [file name => true] Marks files that have already been read */
  private $readMap = [];

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
    // not available; it's not very useful for this kind of cache
    return false;
  }

  function clear ()
  {
    if (!$this->enabled)
      return;
    $tmp = sys_get_temp_dir () . '/' . str_random (8);
    // Ensure an instaneous, atomic removal.
    rename ($this->basePath, $tmp);
    // Try to garbage-collect as much as possible of the directory's contents (some deletions may fail due to files
    // being locked).
    if (@rrmdir ($tmp))
      $this->error ("Couldn't cleanup the $tmp directory", LogLevel::NOTICE);
  }

  function enable ($enabled = true)
  {
    $this->enabled = $enabled;
  }

  /**
   * Loads a class/interface/trait declaration from the cache referenced by the given key and, if it doesn't exist,
   * creates a new entry with the given PHP code and executes it.
   *
   * <p>If the given value is a {@see Closure}, it is called with no arguments and its return value will be used as the
   * source code to be saved.
   *
   * @param string               $key   The FQN (Fully Qualified Name) of the class/interface/trait.
   * @param string|\Closure|null $value A string with the PHP code (with no &lt;?php tag) or a Closure.<br>
   *                                    If NULL, no attempt will be made to cache a value if the key is not on the
   *                                    cache.
   * @return bool TRUE if the item exists or FALSE if it does not and no default value is given.
   */
  function get ($key, $value = null)
  {
    // note: even if the cache is disabled, read operations must execute code only one
    $path = $this->toFileName ($key);
    if (isset($this->readMap[$path]))
      return true;
    if ($this->enabled) {
      // TODO: Slight possibility of reading truncated data here
      if (file_exists ($path)) {
        include $path;
        return true;
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
    }
    // First, compute the value to be saved, if required.
    if (is_object ($value) && $value instanceof \Closure)
      $value = $value ();
    // Save the value and return it, or FALSE if that failed or there's no value to be saved.
    if (isset($value)) {
      if ($this->set ($key, $value, $path)) {
        // TODO: Slight possibility of reading truncated data here
        if ($this->enabled)
          include $path;
        else eval ($value);
        $this->readMap[$path] = true;
        return true;
      }
    }
    return false;
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
    if (!$this->enabled)
      return 0;
    $f = $this->toFileName ($key);
    return file_exists ($f) ? @filemtime ($f) : 0;
  }

  /**
   * Checks if the cache contains a declaration for the given class/interface/trait
   *
   * @param string $key The FQN (Fully Qualified Name) of the class/interface/trait.
   * @return bool
   */
  function has ($key)
  {
    if (!$this->enabled)
      return false;
    return isset($this->readMap[$key]) || file_exists ($this->toFileName ($key));
  }

  function inc ($path, $value = 1)
  {
    // not available; wouldn't make sense with this kind of cache
    return false;
  }

  function isEnabled ()
  {
    return $this->enabled;
  }

  function prune ()
  {
    // not applicable
  }

  function remove ($key)
  {
    if (!$this->enabled)
      return true;
    // The corresponding $readMap entry must NOT be cleared!
    $path = $this->toFileName ($key);
    return @unlink ($path);
  }

  /**
   * Saves a class/interface/trait code declaration on the cache.
   *
   * @param string      $key   The FQN (Fully Qualified Name) of the class/interface/trait.
   * @param string|null $value A string with the PHP code (with no &lt;?php tag).<br>
   *                           Unlike {@see add} or {@see get}, Closures are not invoked and, therefore, can't be
   *                           stored.<br>
   *                           If NULL, the value will not be stored.
   * @param string      $path  For internal use.
   * @return bool TRUE if the item was successfully persisted; FALSE if there was an error or $value is not a string.
   */
  function set ($key, $value, $path = null)
  {
    if (!$this->enabled || is_null ($value))
      return true;
    if (!is_string ($value))
      return false;
    $path = $path ?: $this->toFileName ($key);
    $f    = @fopen ($path, 'w');
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
      if (!fwrite ($f, "<?php\n\n")) return false;
      return fwrite ($f, $value) !== false;
    }
    finally {
      flock ($f, LOCK_UN);
      fclose ($f);
    }
  }

  function setOptions (array $options)
  {
    // no options available
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
