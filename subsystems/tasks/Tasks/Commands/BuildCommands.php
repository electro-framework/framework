<?php
namespace Selenia\Tasks\Commands;
use Robo\Task\Bower;
use Robo\Task\FileSystem\CleanDir;
use Selenia\Assembly\ModulesManager;
use Selenia\Console\Contracts\ConsoleIOServiceTrait;
use Selenia\Contracts\ApplicationServiceTrait;

/**
 * Implements the Selenia Task Runner's pre-set build commands.
 */
trait BuildCommands
{
  use ConsoleIOServiceTrait;
  use ApplicationServiceTrait;

  /**
   * Builds the whole project, including all modules
   * Use this command right after cloning a project or whenever modules are added, removed or updated.
   *
   * @param array $options
   * @option $exclude-libs|x Makes the build run faster by skipping the installation/update of front-end libraries
   *         trough Bower
   */
  function build ($options = ['exclude-libs|x' => false])
  {
    // $this->cleanApp ();
    // $this->cleanModules ();
    if (!$options['exclude-libs']) {
      //$this->cleanLibs ();
      foreach (ModulesManager::get ()->modules () as $module) {
        $path = "$module->path/bower.json";
        if (file_exists ($path))
          copy ($path, $this->app ()->baseDirectory . '/bower.json');
      }
      (new Bower\Update())->dir ($this->app ()->baseDirectory)->run ();
    }
  }

  /**
   * Builds the main project, excluding modules.
   * Use this command whenever you need to recompile/repackage your application's assets.
   */
  function update ()
  {
    $this->io ()->say ("Hello World!");
  }

  /**
   * Cleans the application-specific assets from the public_html/dist folder
   * TODO: make public when ready
   */
  private function cleanApp ()
  {
    (new CleanDir ('public_html/dist'))->run ();
  }

  /**
   * Cleans the front-end libraries from the public_html/lib folder
   * TODO: make public when ready
   */
  private function cleanLibs ()
  {
    (new CleanDir ('public_html/lib'))->run ();
  }

  /**
   * Cleans the module's assets from the public_html/modules folder
   * TODO: make public when ready
   */
  private function cleanModules ()
  {
    (new CleanDir('public_html/modules'))->run ();
  }

}
