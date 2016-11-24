<?php
namespace Electro\Caching\Lib;

use Electro\Interfaces\Caching\CacheInterface;

/**
 * A generic class that allows compilation of source code files to be sped up by using a cache.
 *
 * <p>It supports any compiler and any caching backend.
 */
class CachingFileCompiler
{
  /** @var CacheInterface The underlying cache instance. */
  protected $cache;
  /** @var string */
  protected $sourceFile;
  /** @var bool */
  private $autoSync;
  /** @var bool */
  private $cachingEnabled;

  /**
   * @param CacheInterface $underlyingCache A pre-configured caching backend instance.
   * @param bool           $autoSync        When FALSE the cache will not check file modification times, so cache
   *                                        entries will never expire.
   * @param bool           $cachingEnabled  Should the cache be used (TRUE) or always compile the source (FALSE)?
   */
  public function __construct (CacheInterface $underlyingCache, $autoSync = true, $cachingEnabled = true)
  {
    $this->cache          = $underlyingCache;
    $this->autoSync       = $autoSync;
    $this->cachingEnabled = $cachingEnabled;
  }

  /**
   * Compiles and caches a given source code file.
   *
   * @param string   $sourceFile The filesystem path of the source code file.
   * @param callable $compiler   A function that transforms the source file into the final representation that will be
   *                             cached. It must have a single parameter of type string (the source code).
   * @return mixed The compiled code.
   */
  function cache ($sourceFile, callable $compiler)
  {
    $compiled = $this->loadAndCompile ($sourceFile, $compiler);
    // Note: the following call's return status is not checked as the inner cache takes care of logging errors.
    $this->cache->set ($sourceFile, $compiled);
    return $compiled;
  }

  /**
   * Returns the compiled representation of a given source code file, either from the cache (if available) or by
   * invoking the specified compiler.
   *
   * @param string   $sourceFile The filesystem path of the source code file.
   * @param callable $compiler   A function that transforms the source file into the final representation that will be
   *                             cached. It must have a single parameter of type string (the source code).
   * @return mixed The compiled code.
   */
  function get ($sourceFile, callable $compiler)
  {
    if ($this->autoSync) {
      // Check if the source code has been modified after the compiled code has been generated and cached.
      // If so, re-compile and re-cache it.

      $cacheT  = $this->cache->getTimestamp ($sourceFile);
      $sourceT = @filemtime ($sourceFile);
      if (!$sourceT)
        $this->fileNotFound ($sourceFile);

      // Note: if the cached item doesn't exist yet ($cacheT==FALSE), the following condition also succeeds.
      if ($sourceT > $cacheT)
        return $this->cache ($sourceFile, $compiler);
      // The source file was not modified, so fetch from the cache.
    }
    if ($this->cachingEnabled)
      $this->cache->get ($sourceFile, function () use ($sourceFile, $compiler) {
        return $this->loadAndCompile ($sourceFile, $compiler);
      });
    // Caching is disabled, so just compile the source file and return the result.
    return $this->loadAndCompile ($sourceFile, $compiler);
  }

  /**
   * Checks if a source code file is already cached.
   *
   * @param string $sourceFile The filesystem path of the source code file.
   * @return bool
   */
  function isCached ($sourceFile)
  {
    return $this->cache->has ($sourceFile);
  }

  /**
   * Loads a source code file and compiles it.
   *
   * @param string   $sourceFile The filesystem path of the source code file.
   * @param callable $compiler   A function that transforms the source file into the final representation that will be
   *                             cached. It must have a single parameter of type string (the source code).
   * @return mixed
   */
  function loadAndCompile ($sourceFile, callable $compiler)
  {
    $sourceCode = @file_get_contents ($sourceFile, FILE_TEXT);
    if (!$sourceCode)
      $this->fileNotFound ($sourceFile);
    return $compiler ($sourceCode);
  }

  /**
   * Removes the given source code file's compiled code from the cache.
   *
   * @param string $sourceFile The filesystem path of the source code file.
   * @return bool TRUE if the item was deleted, FALSE if it wasn't cached.
   */
  function uncache ($sourceFile)
  {
    return $this->cache->remove ($sourceFile);
  }

  /**
   * Throws an error for a file that couldn't be loaded.
   *
   * @param string $filename An absolute file path.
   */
  protected function fileNotFound ($filename)
  {
    throw new \RuntimeException("Can't read $filename");
  }

}
