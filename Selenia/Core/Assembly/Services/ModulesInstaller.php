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

  function begin ()
  {
    $this->io->banner ('Selenia Modules Installer');
  }

  /**
   * @param ModuleInfo[] $modules
   */
  function cleanupRemovedModules (array $modules)
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

  private function updateMigrationsOf (ModuleInfo $module)
  {
    $path = "$module->path/" . $this->migrationsSettings->migrationsPath ();
    if (fileExists ($path)) {
      $this->consoleApp->runAndCapture ('migration:status', [$module->name, '--format=json'], $out, false);
      if (!preg_match ('/\{.*\}$/', $out, $m)) return;
      $migrations = json_decode ($m[0]);
      foreach ($migrations->migrations as $migration) {
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
