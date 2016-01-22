<?php
namespace Selenia\Tasks\Commands;

use Robo\Task\File\Replace;
use Robo\Task\FileSystem\CopyDir;
use Robo\Task\FileSystem\DeleteDir;
use Selenia\Console\Traits\ApplicationServiceTrait;
use Selenia\Console\Traits\ConsoleIOServiceTrait;
use Selenia\Console\Traits\FileSystemStackServiceTrait;
use Selenia\Console\Traits\ModuleConfigServiceTrait;
use Selenia\Core\Assembly\Services\ModulesRegistry;
use Selenia\Tasks\Shared\ChmodEx;

/**
 * Implements the Selenia Task Runner's pre-set init:xxx commands.
 */
trait InitCommands
{
  use ConsoleIOServiceTrait;
  use ApplicationServiceTrait;
  use ModuleConfigServiceTrait;
  use FileSystemStackServiceTrait;

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
    $io      = $this->io ();
    $envPath = "{$this->app()->baseDirectory}/.env";
    $io->clear ()
       ->banner ("Selenia Configuration Wizard");
    if (file_exists ($envPath) && !get ($opts, 'overwrite'))
      $io->nl ()->say ("The application is already initialized.")->comment ("Use -o to overwrite.");
    else {
      $io->title ("Creating required files and directories...");
      $this->nestedExec = true;
      $this->initStorage ();
      $this->initConfig ();
    }
    $demoPath = "{$this->app()->modulesPath}/demo-company";
    if (file_exists ($demoPath)) {
      if (!$io->nl ()->confirm ("Do you wish keep the demonstration web pages? [n]")) {
        /** @var ModuleCommands $this */
        $this->moduleUninstall ('demo-company/demo-project');
      }
    }

    $io->done ("Initialization completed successfully");
  }

  /**
   * Initializes the application's configuration (.env file)
   *
   * <p>If a `.env.example` file exists, that file will be copied to `.env`, otherwise a new `.env` file is created.
   *
   * @param array $opts
   * @option $overwrite|o Discards the current .env file if it already exists
   */
  function initConfig ($opts = ['overwrite|o' => false])
  {
    $io      = $this->io ();
    $envPath = "{$this->app()->baseDirectory}/.env";
    if (file_exists ($envPath) && !get ($opts, 'overwrite'))
      $io->error (".env file already exists");

    $examplePath = "{$this->app()->baseDirectory}/.env.example";
    if (file_exists ($examplePath)) {
      $this->fs ()->copy ($examplePath, $envPath, true)->run ();
      $io->nl ()
         ->comment ("The application has been automatically configured from a project-specific predefined template")
         ->comment ("Please edit the <info>.env</info> file to fill-in any missing required values (ex. database passwords)");
    }
    else {
      $this->fs ()->copy ("{$this->moduleConfig('scaffoldsPath')}/.env", $envPath, true)->run ();

      $io->title ("Configuring the application...");

      $LANG = $io->askDefault ("What is the application's main language? (en | pt | ...)", 'en');
      do {
        $DB_DRIVER =
          $io->askDefault ("Which database kind are you going to use? (none | sqlite | mysql | pgsql)", 'none');
      } while ($DB_DRIVER != 'sqlite' && $DB_DRIVER != 'mysql' && $DB_DRIVER != 'pgsql' && $DB_DRIVER != 'none');

      $DB_DATABASE    = '';
      $DB_HOST        = '';
      $DB_USERNAME    = '';
      $DB_PASSWORD    = '';
      $DB_CHARSET     = '';
      $DB_COLLATION   = '';
      $DB_PORT        = '';
      $DB_UNIX_SOCKET = '';

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
          break;
      }
      $io->nl ();
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
        ])
        ->run ();
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
    $target = $this->app ()->storagePath;
    if (file_exists ($target))
      (new DeleteDir ($target))->run ();
    (new CopyDir (["{$this->moduleConfig('scaffoldsPath')}/storage" => $target]))->run ();
    (new ChmodEx ($target))->dirs (0770)->files (0660)->run ();

    (new ModulesRegistry($this->app ()))->rebuildRegistry ();

    if (!$this->nestedExec)
      $this->io ()->done ("Storage directory created");
  }

}
