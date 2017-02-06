<?php
namespace Electro\Tasks\Commands;

use Electro\Configuration\Lib\DotEnv;
use Electro\ConsoleApplication\ConsoleApplication;
use Electro\Exceptions\Fatal\ConfigException;
use Electro\Interfaces\ConsoleIOInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Kernel\Services\ModulesRegistry;
use Electro\Tasks\Config\TasksSettings;
use Electro\Tasks\Shared\ChmodEx;
use Electro\Tasks\Tasks\CoreTasks;
use Robo\Task\File\Replace;
use Robo\Task\FileSystem\CopyDir;
use Robo\Task\FileSystem\DeleteDir;
use Robo\Task\FileSystem\FilesystemStack;

/**
 * Implements the Electro Task Runner's pre-set init:xxx commands.
 *
 * @property KernelSettings     $kernelSettings
 * @property TasksSettings      $settings
 * @property ConsoleApplication $consoleApp
 * @property ConsoleIOInterface $io
 * @property FilesystemStack    $fs
 * @property ModulesRegistry    $modulesRegistry
 */
trait InitCommands
{
  private $nestedExec = false;

  /**
   * Initializes the application after installation, or reinitializes it afterwards
   *
   * Note: this is automatically called after `composer install` runs.
   *
   * @param array $opts
   * @option $overwrite|o Discards the current configuration if it already exists
   */
  function init ($opts = ['overwrite|o' => false])
  {
    $io      = $this->io;
    $envPath = "{$this->kernelSettings->baseDirectory}/.env";
    $io->clear ()
       ->banner ("Electro Configuration Wizard");
    $overwrite = get ($opts, 'overwrite');
    if (file_exists ($envPath) && !$overwrite)
      $io->say ("<warning>The application is already configured</warning>")
         ->comment ("Use -o to overwrite the current configuration");
    else {
      $this->nestedExec = true;
      ensureDir ("{$this->kernelSettings->baseDirectory}/{$this->kernelSettings->modulesPath}");
      ensureDir ("{$this->kernelSettings->baseDirectory}/{$this->kernelSettings->pluginModulesPath}");
      $this->initStorage ();
      $this->initConfig (['overwrite' => true]);
      $this->loadConfig ();
    }

    // (Re)initialize all plugins and private modules.

    $io->title ('Initializing modules');

    /** @var $this CoreTasks */
    foreach ($this->modulesRegistry->onlyPrivateOrPlugins ()->getModuleNames () as $moduleName)
      $this->modulesInstaller->setupModule ($moduleName, true);

    $io->done ("Initialization completed successfully");
  }

  /**
   * Initializes the application's environment configuration (.env file)
   *
   * <p>If a `.env.example` file exists, that file will be copied to `.env`, otherwise a new `.env` file is created.
   *
   * @param array $opts
   * @option $overwrite|o Discards the current .env file if it already exists
   */
  function initConfig ($opts = ['overwrite|o' => false])
  {
    $io      = $this->io;
    $envPath = "{$this->kernelSettings->baseDirectory}/.env";
    if (file_exists ($envPath) && !get ($opts, 'overwrite'))
      $io->error (".env file already exists");

    $examplePath = "{$this->kernelSettings->baseDirectory}/.env.example";
    if (file_exists ($examplePath)) {
      $io->mute ();
      $this->fs->copy ($examplePath, $envPath, true)->run ();
      $io->unmute ();
      $io->nl ()
         ->comment ("The application has been automatically configured from a project-specific predefined template")
         ->comment ("Please edit the <info>.env</info> file to fill-in any missing required values (ex. database passwords)");
    }
    else {
      $io->mute ();
      $this->fs->copy ("{$this->settings->scaffoldsPath()}/.env", $envPath, true)->run ();
      $io->unmute ();

      $LANG = $io->askDefault ("What is the application's main language? (en | pt | ...)", 'en');
      do {
        $DB_DRIVER =
          $io->askDefault ("Which database kind are you going to use? (none | sqlite | mysql | pgsql)", 'sqlite');
      } while ($DB_DRIVER != 'sqlite' && $DB_DRIVER != 'mysql' && $DB_DRIVER != 'pgsql' && $DB_DRIVER != 'none');

      $DB_DATABASE    = '';
      $DB_HOST        = '';
      $DB_USERNAME    = '';
      $DB_PASSWORD    = '';
      $DB_CHARSET     = 'utf8              ; must be set or the collation will not take affect';
      $DB_COLLATION   = 'utf8_unicode_ci   ; to know why, see ' .
                        'http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci#answer-766996';
      $DB_PORT        = '';
      $DB_UNIX_SOCKET = '';
      $DB_PREFIX      = '';

      switch ($DB_DRIVER) {
        case 'sqlite':
          $DB_DATABASE = "private/storage/database/db.sqlite";
          break;
        case 'mysql':
          $DB_DATABASE = $io->ask ("Database name");
          if (!$DB_DATABASE) $io
            ->comment ("Database name will be determined by MySQL from the username (if so configured)");
          $DB_HOST     = $io->askDefault ("Database host domain", env ('DB_HOST', 'localhost'));
          $DB_USERNAME = $io->askDefault ("Database username", env ('DB_USERNAME'));
          $DB_PASSWORD = $io->askDefault ("Database password", env ('DB_PASSWORD'));
          if ($io->confirm ("Do you whish to set advanced database connection options? [n]")) {
            $DB_CHARSET     = $io->askDefault ("Database character set", 'utf8');
            $DB_COLLATION   = $io->askDefault ("Database collation", 'utf8_unicode_ci');
            $DB_PORT        = $io->ask ("Database port [disable]");
            $DB_UNIX_SOCKET = $io->ask ("Database UNIX socket [disable]");
            $DB_PREFIX      = $io->askDefault ("Prefix this application's tables", env ('DB_PREFIX'));
          }
          else {
            $DB_CHARSET   = 'utf8';
            $DB_COLLATION = 'utf8_unicode_ci';
          }
          break;
        case 'pgsql':
          do {
            $DB_DATABASE = $io->ask ("Database name");
            if (!$DB_DATABASE) $io->say ("You must specify a name");
          } while (!$DB_DATABASE);
          $DB_HOST     = $io->askDefault ("Database host domain", env ('DB_HOST', 'localhost'));
          $DB_USERNAME = $io->askDefault ("Database username", env ('DB_USERNAME'));
          $DB_PASSWORD = $io->askDefault ("Database password", env ('DB_PASSWORD'));
          if ($io->confirm ("Do you whish to set advanced database connection options? [n]")) {
            $DB_PREFIX = $io->askDefault ("Prefix this application's tables", env ('DB_PREFIX'));
          }
          break;
      }
      $io->mute ();
      (new Replace ($envPath))
        ->from ([
          '%LANG',
          '%DB_DRIVER',
          '%DB_DATABASE',
          '%DB_HOST',
          '%DB_USERNAME',
          '%DB_PASSWORD',
          '%DB_CHARSET',
          '%DB_COLLATION',
          '%DB_PORT',
          '%DB_UNIX_SOCKET',
          '%DB_PREFIX',
        ])
        ->to ([
          $LANG,
          $DB_DRIVER,
          $DB_DATABASE,
          $DB_HOST,
          $DB_USERNAME,
          $DB_PASSWORD,
          $DB_CHARSET,
          $DB_COLLATION,
          $DB_PORT,
          $DB_UNIX_SOCKET,
          $DB_PREFIX,
        ])
        ->run ();
      $io->unmute ();
    }
    if (!$this->nestedExec)
      $io->done ("Initialization completed successfully");
  }

  /**
   * Scaffolds the storage directory's structure
   *
   * <p>It discards the target directory if it already exists.
   * > <p>This is automatically run after composer-create-project.
   * If you cloned the starter project by other means, run this command manually.
   */
  function initStorage ()
  {
    $this->io->mute ();
    $target = $this->kernelSettings->storagePath;
    if (file_exists ($target))
      (new DeleteDir ($target))->run ();
    (new CopyDir (["{$this->settings->scaffoldsPath()}/storage" => $target]))->run ();
    (new ChmodEx ($target))->dirs (0775)->files (0664)->run ();

    $this->consoleApp->run ('module:refresh');
    $this->io->unmute ();

    if (!$this->nestedExec)
      $this->io->done ("Storage directory created");
  }

  /**
   * @return void
   * @throws ConfigException
   */
  private function loadConfig ()
  {
    $dotenv = new Dotenv ("{$this->kernelSettings->baseDirectory}/.env");
    $dotenv->load ();
  }

}
