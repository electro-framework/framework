<?php

namespace Electro\Tasks\Tasks;

use Electro\Caching\Config\CachingSettings;
use Electro\ConsoleApplication\ConsoleApplication;
use Electro\ConsoleApplication\Lib\ModulesUtil;
use Electro\ConsoleApplication\Services\ConsoleIO;
use Electro\Kernel\Config\KernelSettings;
use Electro\Kernel\Services\ModulesInstaller;
use Electro\Kernel\Services\ModulesRegistry;
use Electro\Tasks\Commands\InitCommands;
use Electro\Tasks\Commands\MiscCommands;
use Electro\Tasks\Commands\ModuleCommands;
use Electro\Tasks\Commands\ServerCommands;
use Electro\Tasks\Commands\UpdateCommand;
use Electro\Tasks\Config\TasksSettings;
use Electro\Tasks\Shared\Base\ComposerTask;
use PhpKit\Flow\FilesystemFlow;
use Robo\Task\FileSystem\CleanDir;
use Robo\Task\FileSystem\DeleteDir;
use Robo\TaskAccessor;
use Robo\Tasks;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The preset Electro console tasks configuration for Electro's task runner.
 */
class CoreTasks extends Tasks
{
  // use TaskAccessor;
  use InitCommands;
//  use BuildCommands;
  use ModuleCommands;
  use UpdateCommand;
  use ServerCommands;
  use MiscCommands;

  /**
   * @var bool Display the output of Composer commands?
   */
  static $SHOW_COMPOSER_OUTPUT = true;
  /**
   * @var CachingSettings
   */
  private $cachingSettings;
  /**
   * @var ConsoleApplication
   */
  private $consoleApp;
  /**
   * @var Filesystem
   */
  private $fs;
  /**
   * @var ConsoleIO
   */
  protected $io;
  /**
   * @var KernelSettings
   */
  private $kernelSettings;
  /**
   * @var ModulesInstaller
   */
  private $modulesInstaller;
  /**
   * @var ModulesRegistry
   */
  private $modulesRegistry;
  /**
   * @var ModulesUtil
   */
  private $modulesUtil;
  /**
   * @var TasksSettings
   */
  private $settings;

  function __construct (ConsoleIO $io, KernelSettings $kernelSettings, ModulesUtil $modulesUtil,
                        ModulesRegistry $registry, ModulesInstaller $installer, TasksSettings $settings,
                        ConsoleApplication $consoleApp, CachingSettings $cachingSettings)
  {
    $this->io               = $io;
    $this->modulesUtil      = $modulesUtil;
    $this->kernelSettings   = $kernelSettings;
    $this->modulesRegistry  = $registry;
    $this->modulesInstaller = $installer;
    $this->settings         = $settings;
    $this->consoleApp       = $consoleApp;
    $this->fs               = new Filesystem;
    $this->cachingSettings  = $cachingSettings;
  }

  /**
   * Clears the content of a directory.
   *
   * @param string $path An absolute or relative filesystem path.
   */
  private function clearDir ($path)
  {
    $this->task (CleanDir::class, $this->kernelSettings->toAbsolutePath ($path))->run ();
  }

  /**
   * Runs the `composer update` command.
   */
  private function doComposerUpdate ()
  {
    $this->task (ComposerTask::class)->action('update')->printOutput(self::$SHOW_COMPOSER_OUTPUT)->run();
  }

  /**
   * Check if a directory is empty.
   *
   * @param string $path
   * @return bool
   */
  private function isDirectoryEmpty ($path)
  {
    return !count (FilesystemFlow::from ($path)->all ());
  }

  /**
   * Removes a directory if it's empty.
   *
   * @param string $path
   * @return bool TRUE if the directory was removed.
   */
  private function removeDirIfEmpty ($path)
  {
    if ($this->isDirectoryEmpty ($path)) {
      $this->task (DeleteDir::class, $path)->run ();
      return true;
    }
    return false;
  }

}
