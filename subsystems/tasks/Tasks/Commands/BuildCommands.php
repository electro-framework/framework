<?php
namespace Electro\Tasks\Commands;

use Electro\Interfaces\ConsoleIOInterface;
use Electro\Kernel\Config\KernelSettings;
use Robo\Task\FileSystem\CleanDir;

/**
 * Implements the Electro Task Runner's pre-set build commands.
 *
 * @property KernelSettings     $app
 * @property ConsoleIOInterface $io
 */
trait BuildCommands
{
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
//      foreach (ModulesLoader::get ()->modules () as $module) {
//        $path = "$module->path/bower.json";
//        if (file_exists ($path))
//          copy ($path, $this->kernelSettings->baseDirectory . '/bower.json');
//      }
//      (new Bower\Update())->dir ($this->kernelSettings->baseDirectory)->run ();
    }
  }

  /**
   * Builds the main project, excluding modules.
   * Use this command whenever you need to recompile/repackage your application's assets.
   */
  function update ()
  {
    $this->io->say ("'update' is not yet implemented");
  }

  /**
   * Cleans the application-specific assets from the public_html/dist folder
   * TODO: make public when ready
   */
  private function cleanApp ()
  {
    $this->task (CleanDir::class, 'public_html/dist')->run ();
  }

  /**
   * Cleans the front-end libraries from the public_html/lib folder
   * TODO: make public when ready
   */
  private function cleanLibs ()
  {
    $this->task (CleanDir::class, 'public_html/lib')->run ();
  }

  /**
   * Cleans the module's assets from the public_html/modules folder
   * TODO: make public when ready
   */
  private function cleanModules ()
  {
    $this->task (CleanDir::class, 'public_html/modules')->run ();
  }

}
