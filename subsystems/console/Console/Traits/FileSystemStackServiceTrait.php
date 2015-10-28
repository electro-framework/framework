<?php
namespace Selenia\Console\Traits;

use Robo\Task\FileSystem\FilesystemStack;

/**
 * Allows traits to access the FilesystemStack service.
 */
trait FileSystemStackServiceTrait
{
  /**
   * @return FilesystemStack
   */
  protected abstract function fs ();
}
