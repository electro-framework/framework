<?php
namespace Selene;
use Robo\Result;
use Robo\Tasks;

/**
 * The preset Selene console commands configuration for Selene's task runner.
 */
class PresetTasks extends Tasks
{
  function __construct ()
  {
    global $application, $argc;
    if (!isset($application)) {
      $this->say ("Selene tasks must be run from the 'selene' command.");
      exit (1);
    }
    $NL = PHP_EOL;
    $this->getOutput ()
         ->writeln ("========================{$NL}   Selene Task Runner$NL========================");
    $this->getOutput ()->write ($argc < 2 ? "Using " : $NL);
  }

  /**
   * Builds the whole project, including all modules
   * Use this command right after cloning a project or whenever modules are added, removed or updated.
   * @param array $options
   * @option $exclude-bower|x Makes the build run faster by skipping the installation/update of front-end libraries
   *         trough Bower
   */
  function build ($options = ['exclude-libs|x' => false])
  {
    $this->cleanApp ();
    $this->cleanModules ();
    if (!$options['exclude-libs'])
      $this->cleanLibs ();
  }

  /**
   * Cleans the application-specific assets from the public_html/dist folder
   */
  function cleanApp ()
  {
    $this->taskCleanDir ('public_html/dist')->run ();
  }

  /**
   * Cleans the front-end libraries from the public_html/lib folder
   */
  function cleanLibs ()
  {
    $this->taskCleanDir ('public_html/lib')->run ();
  }

  /**
   * Cleans the module's assets from the public_html/modules folder
   */
  function cleanModules ()
  {
    $this->taskCleanDir ('public_html/modules')->run ();
  }

  /**
   * Scaffolds a new module for your application
   * @param string $name vendorName/moduleName
   * @return Result
   */
  function createModule ($name = '')
  {
    global $application;
    if (!$name)
      $name = $this->ask ("Module name (vendor/name)?");
    if ($name) {
      if (count (explode ('/', $name)) != 2)
        $this->error ("Invalid module name.");
      $path = "$application->modulesPath/$name";
      if (file_exists ($path) || file_exists ("$application->defaultModulesPath/$name"))
        $this->error ("Module $name already exists.");
      $this->_mkDir ($path);
      $this->_copyDir ("$application->frameworkPath/$application->scaffoldsPath/module", $path);

      $this->say ("Module <info>$name</info> created.");
    }
  }

  /**
   * Scaffolds the storage directory's structure
   *
   * This is automaticallu run after composer-create-project.
   * If you cloned the starter project by other means, run this command manually.
   * @return Result
   */
  function createStorage ()
  {
    global $application;
    $this->_deleteDir (["$application->storagePath"]);
    $this->_copyDir ("$application->frameworkPath/$application->scaffoldsPath/storage", "$application->storagePath");

    $this->say ("Storage folder created.");
  }

  /**
   * Builds the main project, excluding modules.
   * Use this command whenever you need to recompile/repackage your application's assets.
   */
  function update ()
  {
    $this->yell ("Hello World!");
  }

  protected function error ($text, $length = 40)
  {
    if (strlen ($text) < $length - 4)
      $length = strlen ($text) + 4;
    $o      = $this->getOutput ();
    $format = "<fg=white;bg=red;options=bold>%s</fg=white;bg=red;options=bold>";
    $text   = str_pad ($text, $length, ' ', STR_PAD_BOTH);
    $len    = strlen ($text) + 2;
    $space  = str_repeat (' ', $len);
    $o->writeln (sprintf ($format, $space));
    $o->writeln (sprintf ($format, " $text "));
    $o->writeln (sprintf ($format, $space));
    exit (1);
  }
}
