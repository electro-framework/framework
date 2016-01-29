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
  /**
   * @var ModulesRegistry
   */
  private $registry;

  function __construct (Application $app, ConsoleApplication $consoleApp)
  {
    $this->app        = $app;
    $this->consoleApp = $consoleApp;
    $this->io         = $consoleApp->getIO ();
  }

  function begin ()
  {
    $this->io->banner ('Selenia Modules Installer');
  }

  /**
   * Performs uninstallation clean up tasks before the module is actually uninstalled.
   *
   * @param string $moduleName
   */
  function cleanUpModule ($moduleName)
  {
    $this->io->writeln ("Cleaning up <info>$moduleName</info>");

    if ($this->moduleHasMigrations ($moduleName)) {
      $migrations = $this->getMigrationsOf ($moduleName);
      if ($migrations) {
        $firstMigration = array_pop ($migrations);
        $this->io->nl ()->say ("Updating the database (migration id $firstMigration->migration_id)");
        $this->consoleApp->runAndCapture ('migration:rollback',
          ['--target=' . $firstMigration->migration_id, $moduleName],
          $out, true, $this->io->getOutput ()->getVerbosity ());
        $this->io->write ($out);
      }
    }
  }

  /**
   * @param ModuleInfo[] $modules
   */
  function cleanUpRemovedModules (array $modules)
  {
    if ($modules)
      $this->io
        ->title ('Cleaning-up Removed Modules')
        ->writeln ('  <info>■</info> ' . implode ("\n  <info>■</info> ",
            ModulesRegistry::getNames ($modules)))
        ->nl ();
  }

  function end ()
  {
    $this->io
      ->writeln ('<info>Modules configuration completed</info>')
      ->nl ();
  }

  function setRegistry (ModulesRegistry $registry)
  {
    $this->registry = $registry;
  }

  /**
   * @param ModuleInfo[] $modules
   */
  function setupNewModules (array $modules)
  {
    if ($modules)
      $this->io
        ->title ('Configuring New Modules')
        ->writeln ('  <info>■</info> ' . implode ("\n  <info>■</info> ",
            ModulesRegistry::getNames ($modules)))
        ->nl ();
  }

  /**
   * @param ModuleInfo[] $modules
   */
  function updateModules (array $modules)
  {
    if (!$modules) return;

    $this->io->title ("Re-check Installed Modules");

    $databaseIsAvailable = Connection::getFromEnviroment ()->isAvailable ();
    $runMigrations       = $databaseIsAvailable && $this->migrationsSettings;

//    var_dump($modules);exit;
    foreach ($modules as $module) {
      $this->io->writeln ("  <info>■</info> $module->name");
      if ($runMigrations)
        $this->updateMigrationsOf ($module);
    }
    $this->io->nl ();
  }

  /**
   * @param string $moduleName
   * @return \stdClass[]
   */
  private function getMigrationsOf ($moduleName)
  {
    $this->consoleApp->runAndCapture ('migration:status', [$moduleName, '--format=json'], $out, false);
    if (!preg_match ('/\{.*\}$/', $out, $m)) return [];
    return json_decode ($m[0])->migrations;
  }

  /**
   * @param string|ModuleInfo $module
   * @return bool
   */
  private function moduleHasMigrations ($module)
  {
    if (is_string ($module))
      $module = $this->registry->getModule ($module);
    $path = "$module->path/" . $this->migrationsSettings->migrationsPath ();
    return fileExists ($path);
  }

  private function updateMigrationsOf (ModuleInfo $module)
  {
    if ($this->moduleHasMigrations ($module)) {
      $migrations = $this->getMigrationsOf ($module->name);
      foreach ($migrations as $migration) {
        if ($migration->migration_status == 'down') {
          $this->io->nl ()->say ("    Updating the database");
          $this->consoleApp->runAndCapture ('migration:run', [$module->name], $out, true,
            $this->io->getOutput ()->getVerbosity ());
          $this->io->indent (4)->write ($out)->indent ()->nl ();
          break;
        }
      }
    }
  }

}
