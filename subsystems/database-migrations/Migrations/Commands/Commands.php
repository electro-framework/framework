<?php
namespace Selenia\Migrations\Commands;

use Phinx\Console\Command;
use Robo\Config;
use Selenia\Console\Lib\ModulesUtil;
use Selenia\Console\Services\ConsoleIO;
use Selenia\Core\Assembly\Services\ModulesRegistry;
use Symfony\Component\Console\Application as SymfonyConsole;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Database migration commands.
 */
class Commands
{
  /**
   * @var string Used internally to pass information to config.php
   */
  static $migrationsPath;
  /**
   * @var string Used internally to pass information to config.php
   */
  static $migrationsTable;
  /**
   * @var SymfonyConsole
   */
  private $console;
  /**
   * @var ConsoleIO
   */
  private $io;
  /**
   * @var ModulesUtil
   */
  private $modulesUtil;
  /**
   * @var ModulesRegistry
   */
  private $registry;

  function __construct (ConsoleIO $io, ModulesRegistry $registry, ModulesUtil $modulesUtil, SymfonyConsole $console)
  {
    $this->io         = $io;
    $this->registry = $registry;
    $this->modulesUtil = $modulesUtil;
    $this->console = $console;
  }

  /**
   * Gets the specified setting from the module's configuration.
   * @param string $key
   * @return mixed
   */
//  public function config ($key)
//  {
//    return get ($this->app ()->config['migrations'], $key);
//  }

  /**
   * Create a new database migration
   *
   * @param string $moduleName The target module (vendor-name/package-name syntax).
   *                           If not specified, the user will be prompted for it
   * @param string $name       The name of the migration (a valid PHP class name)
   *                           If not specified, the user will be prompted for it
   * @param array  $options
   * @option $class|l Use a class implementing "Phinx\Migration\CreationInterface" to generate the template
   * @option $template|t Use an alternative template
   * @return int Status code
   */
  function migrationCreate ($moduleName = null, $name = null, $options = [
    'class|l'    => null,
    'template|t' => null,
  ])
  {
    $this->setupModule ($moduleName);
    while (!$name) {
      $name = str_camelize ($this->io->ask ("Migration description:"), true);
    }

    $command = new Command\Create;
    $command->setApplication ($this->console);
    $input  = new ArrayInput(PA ([
      '--configuration' => dirname (__DIR__) . '/config.php',
      'name'            => $name,
      '--class'         => $options['class'],
      '--template'      => $options['template'],
      $moduleName,
    ])->prune ()->A);
    $output = Config::get ('output');

    return $command->run ($input, $output);
  }

  /**
   * Reverts the last migration of a specific module, or optionally up to a specific version
   *
   * @param string $moduleName The target module (vendor-name/package-name syntax).
   *                           If not specified, the user will be prompted for it
   * @param array  $options
   * @option $target|t The version number to rollback to
   * @return int Status code
   */
  function migrationRollback ($moduleName = null, $options = [
    'target|t' => null,
  ])
  {
    $this->setupModule ($moduleName);

    $command = new Command\Rollback;
    $command->setApplication ($this->console);
    $input  = new ArrayInput(PA ([
      '--configuration' => dirname (__DIR__) . '/config.php',
      '--target'        => $options['target'],
      '--environment'   => 'main',
      $moduleName,
    ])->prune ()->A);
    $output = Config::get ('output');

    return $command->run ($input, $output);
  }

  /**
   * Run all available migrations of a specific module, optionally up to a specific version
   *
   * @param string $moduleName The target module (vendor-name/package-name syntax).
   *                           If not specified, the user will be prompted for it
   * @param array  $options
   * @option $target|t The version number to migrate to
   * @return int Status code
   */
  function migrationRun ($moduleName = null, $options = [
    'target|t' => null,
  ])
  {
    $this->setupModule ($moduleName);

    $command = new Command\Migrate;
    $command->setApplication ($this->console);
    $input  = new ArrayInput(PA ([
      '--configuration' => dirname (__DIR__) . '/config.php',
      '--target'        => $options['target'],
      '--environment'   => 'main',
      $moduleName,
    ])->prune ()->A);
    $output = Config::get ('output');

    return $command->run ($input, $output);
  }

  /**
   * Print a list of all migrations of a specific module, along with their current status
   *
   * @param string $moduleName The target module (vendor-name/package-name syntax).
   *                           If not specified, the user will be prompted for it
   * @param array  $options
   * @option $format|f      The output format: 'text' or 'json'
   * @return int Status code
   */
  function migrationStatus ($moduleName = null, $options = [
    'format|f' => 'text',
  ])
  {
    $this->setupModule ($moduleName);

    $command = new Command\Status;
    $command->setApplication ($this->console);
    $input  = new ArrayInput(PA ([
      '--configuration' => dirname (__DIR__) . '/config.php',
      '--format'        => $options['format'],
      '--environment'   => 'main',
      $moduleName,
    ])->prune ()->A);
    $output = Config::get ('output');

    return $command->run ($input, $output);
  }

  /**
   * Sets the migrations folder path for subsequent commands.
   * @param string $moduleName vendor-name/package-name
   */
  protected function setupMigrationConfig ($moduleName)
  {
    self::$migrationsPath  = $this->registry->getModule($moduleName)->path . '/migrations';
    self::$migrationsTable = 'migrations_of_' . str_replace ('/', '_', dehyphenate ($moduleName));
  }

  /**
   * Prepares the migrations context for running on the specified module.
   *
   * It also validates the module name and/or asks for it, if empty. In the later case, the `$moduleName` argument will
   * be updated on the caller.
   * @param string $moduleName vendor-name/package-name
   */
  private function setupModule (&$moduleName)
  {
    $this->modulesUtil->selectModule($moduleName);
    $this->setupMigrationConfig ($moduleName);
  }

}
