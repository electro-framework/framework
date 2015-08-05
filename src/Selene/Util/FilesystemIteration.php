<?php
namespace Selene\Util;
use FilesystemIterator;

class FilesystemIteration extends Iteration
{
  /**
   * Creates a filesystem directory query.
   * @param string $path  The directory path.
   * @param int    $flags One of the FilesystemIterator::XXX flags.<br>
   *                      Default = KEY_AS_PATHNAME | CURRENT_AS_FILEINFO | SKIP_DOTS
   * @return static
   */
  static function from ($path, $flags = 4096)
  {
    return new static (new \FilesystemIterator($path, $flags));
  }

  function onlyDirectories ()
  {
    return $this->where (function (\SplFileInfo $f) { return $f->isDir (); });
  }

  function onlyFiles ()
  {
    return $this->where (function (\SplFileInfo $f) { return $f->isFile (); });
  }

}
