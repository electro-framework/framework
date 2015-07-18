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
    global $application;
    if (!isset($application)) {
//      $this->say ("Selene tasks must be run from the 'selene' command.");
//      exit;
    }
    $NL = PHP_EOL;
    $this->getOutput ()
         ->write ("========================{$NL}   Selene Task Runner$NL========================{$NL}Using ");
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
      $path = "$application->modulesPath/$name";
      if (file_exists ($path) || file_exists ("$application->defaultModulesPath/$name"))
        return Result::error ($this, "Module $name already exists.");
      $this->_mkDir ($path);
      $this->_copyDir ("$application->frameworkPath/$application->scaffoldsPath/module", $path);

      return Result::success ($this, "Module $name created.");
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

    return Result::success ($this, "Storage folder created.");
  }

  /**
   * Builds the main project, excluding modules.
   * Use this command whenever you need to recompile/repackage your application's assets.
   */
  function update ()
  {
    $this->yell ("Hello World!");
  }
}
