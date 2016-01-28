<?php
namespace Selenia\Core\Assembly\Services;

use PhpKit\Connection;
use Selenia\Application;
use Selenia\Core\Assembly\ModuleInfo;
use Selenia\Core\ConsoleApplication\ConsoleApplication;
use Selenia\Interfaces\ConsoleIOInterface;
use Selenia\Migrations\Config\MigrationsSettings;

/**
 * Manages modules installation, update and removal.
 */
class ModulesInstaller
{
  /**
   * @var MigrationsSettings
   */
  public $migrationsSettings;
  /**
   * @var Application
   */
  private $app;
  /**
   * @var ConsoleApplication
   */
  private $consoleApp;
  /**
   * @var ConsoleIOInterface
   */
  private $io;

  public function __construct (Application $app, ConsoleApplication $consoleApp)
  {
    $this->app        = $app;
    $this->consoleApp = $consoleApp;
    $this->io         = $consoleApp->getIO ();
  }

  /**
   * @param ModuleInfo[] $modules
   */
  function cleanupRemovedModules (array $modules)
  {
    if ($modules)
      $this->io->writeln ("<comment>REMOVED MODULES:</comment>\n<info>■</info> " .
                          implode ("\n<info>■</info> ", $modules))
               ->nl ();
  }

  /**
   * @param ModuleInfo[] $modules
   */
  function setupNewModules (array $modules)
  {
    if ($modules)
      $this->io->writeln ("<comment>NEW MODULES:</comment>\n<info>■</info> " . implode ("\n<info>■</info> ", $modules))
               ->nl ();
  }

  /**
   * @param ModuleInfo[] $modules
   */
  function updateModules (array $modules)
  {
    if (!$modules) return;

    $this->io->title ("Re-check modules");

    $databaseIsAvailable = Connection::getFromEnviroment ()->isAvailable ();
    $runMigrations       = $databaseIsAvailable && $this->migrationsSettings;

//    var_dump($modules);exit;
    foreach ($modules as $module) {
      $this->io->writeln ("<info>■</info> $module->name");
      if ($runMigrations)
        $this->updateMigrationsOf ($module);
    }
    $this->io->nl ();
  }

  private function updateMigrationsOf (ModuleInfo $module)
  {
    $path = "$module->path/" . $this->migrationsSettings->migrationsPath ();
    if (fileExists ($path)) {
      $this->io->nl ()->say ("Running migrations of module <info>$module->name</info>");
      $this->consoleApp->run ('migration:run', [$module->name]);
      $this->io->nl ();
    }
  }

}
