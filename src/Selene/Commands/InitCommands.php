<?php
namespace Selene\Commands;
use Robo\Task\FileSystem\CopyDir;
use Robo\Task\FileSystem\DeleteDir;
use Selene\Tasks\ChmodEx;
use Selene\Traits\CommandAPI;

/**
 * Implmenents the Selene task runner's pre-set init:xxx commands.
 */
trait InitCommands
{
  use CommandAPI;

  /**
   * Initializes the application after installation
   *
   * This is automatically called after `composer install` runs.
   */
  function init ()
  {
    $this->initStorage ();
    if (!file_exists ("{$this->app->baseDirectory}/.env"))
      $this->initConfig ();
  }

  /**
   * Initializes the application's configuration (.env file)
   * @param array $opts
   * @option $overwrite|o Discards the current .env file if it already exists
   */
  function initConfig ($opts = ['overwrite|o' => false])
  {
    $envPath = "{$this->app->baseDirectory}/.env";
    if (file_exists ($envPath) && !$opts['overwrite'])
      $this->error (".env file already exists");
    $this->fs ()->copy ("{$this->app->scaffoldsPath}/.env", $envPath)->run ();

    $LANG      = $this->askDefault ("What is the application's default language? (en | pt | ...)", 'en');
    $DB_DRIVER = $this->askDefault ("Which database are you going to use? (sqlite | mysql)", 'sqlite');
    if ($DB_DRIVER == 'sqlite')
      $DB_DATABASE = "../private/storage/database/db.sqlite";
    else {
      $DB_DATABASE = $this->ask ("Database name");
      $DB_HOST     = $this->askDefault ("Database host domain", 'localhost');
      $DB_USERNAME = $this->ask ("Database username");
      $DB_PASSWORD = $this->ask ("Database password");
      if ($this->confirm ("Do you which to set advanced database connection options?")) {
        $DB_CHARSET     = $this->askDefault ("Database character set", 'utf8');
        $DB_COLLATION   = $this->askDefault ("Database collation", 'utf8_unicode_ci');
        $DB_PORT        = $this->ask ("Database port (leave empty for default)");
        $DB_UNIX_SOCKET = $this->ask ("Database UNIX socket (leave empty for default)");
      }
      else {
        $DB_CHARSET     = 'utf8';
        $DB_COLLATION   = 'utf8_unicode_ci';
        $DB_PORT        = '';
        $DB_UNIX_SOCKET = '';
      }
    }

  }

  /**
   * Scaffolds the storage directory's structure
   *
   * This is automatically run after composer-create-project.
   * If you cloned the starter project by other means, run this command manually.
   *
   * @param array $opts
   * @option $overwrite|o Discards the target directory if it already exists
   */
  function initStorage ($opts = ['overwrite|o' => false])
  {
    $target = $this->app->storagePath;
    if (file_exists ($target)) {
      if ($opts['overwrite'])
        (new DeleteDir ($target))->run ();
      else $this->error ("Directory already exists");
    }
    (new CopyDir (["{$this->app->frameworkPath}/{$this->app->scaffoldsPath}/storage" => $target]))->run ();
    (new ChmodEx ($target))->dirs (0770)->files (0660)->run ();

    $this->say ("Storage directory created");
  }

}
