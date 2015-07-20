<?php
namespace Selene\Commands;
use Robo\Task\FileSystem\CopyDir;
use Selene\Traits\CommandAPIInterface;

/**
 * Implmenents the Selene task runner's pre-set build commands.
 */
trait CreateCommands
{
  use CommandAPIInterface;

  /**
   * Scaffolds a new module for your application
   * @param string $name vendorName/moduleName
   */
  function createModule ($name = '')
  {
    if (!$name)
      $name = $this->ask ("Module name (in hyphenated vendor-name/module-name form)?");
    if ($name) {
      $o = explode ('/', $name);
      if (count ($o) != 2)
        $this->error ("Invalid module name.");
      $class = ucfirst (dehyphenate ($o[1]));
      $path  = "{$this->app()}->modulesPath/$name";
      if (file_exists ($path) || file_exists ("{$this->app()}->defaultModulesPath/$name"))
        $this->error ("Module $name already exists.");
      $this->fs()->mkdir ($path)->run();
      (new CopyDir ("{$this->app()}->frameworkPath/{$this->app()}->scaffoldsPath/module", $path))->run();

      $this->say ("Module <info>$name</info> created.");
    }
  }

}
