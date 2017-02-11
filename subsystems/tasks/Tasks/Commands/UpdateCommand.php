<?php

namespace Electro\Tasks\Commands;

use Electro\Interfaces\ConsoleIOInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Kernel\Services\ModulesRegistry;

/**
 * Defines a command that rebuilds the project's composer.json file by merging relevant sections from the project
 * modules' composer.json files.
 * Whenever you modify a module's composer.json, you should run this command and then commit the changes to VCS.
 *
 * @property ModulesRegistry    $modulesRegistry
 * @property ConsoleIOInterface $io
 */
trait UpdateCommand
{
  public static $ALLOW_EXTRA_KEYS = ['bower'];

  /**
   * Rebuilds the project's composer.json to reflect the dependencies of all project modules and installs/updates them
   */
  function updateComposer ()
  {
    $rootFile = 'composer.root.json';
    if (!fileExists ($rootFile))
      $this->io->error ("A <error-info>$rootFile</error-info> file was not found at the project's root directory");
    $targetConfig = json_load ($rootFile, true);

    $requires = $requiredBy = $psr4s = $bins = $files = $extra = [];
    $modules  = $this->modulesRegistry->onlyPrivate ()->getModules ();

    foreach ($modules as $module) {
      $config = $module->getComposerConfig ();

      // Merge 'require' section

      if ($require = $config->get ('require')) {
        foreach ($require as $name => $version) {
          // Exclude project modules and subsystems
          $p = $this->modulesRegistry->getModule ($name);
          if ($p && $p->type != ModuleInfo::TYPE_PLUGIN)
            continue;

          if (!isset ($requires[$name])) {
            $requires[$name]   = $version;
            $requiredBy[$name] = $module->name;
          }
          else if ($requires[$name] != $version)
            $this->io->error ("<error-info>{$module->name}</error-info> requires <error-info>$name@$version</error-info>, which conflicts with <error-info>$requiredBy[$name]</error-info>'s required version <error-info>$requires[$name]</error-info> of the same package");
        }
      }

      // Merge 'autoload.psr-4' section

      foreach ($config->get ('autoload.psr-4', []) as $namespace => $dir)
        $psr4s[$namespace] = "$module->path/$dir";

      // Merge 'files' section

      foreach ($config->get ('autoload.files', []) as $file)
        $files[] = "$module->path/$file";

      // Merge 'bin' section

      foreach ($config->get ('bin', []) as $file)
        $bins[] = "$module->path/$file";

      // Merge 'extra' section

      foreach ($config->get ('extra', []) as $k => $v)
        if (in_array ($k, self::$ALLOW_EXTRA_KEYS))
          $extra[$k] = array_replace_recursive (get ($extra, $k, []), $v);
    }

    ksort ($requires);
    ksort ($psr4s);
    ksort ($bins);
    // do not ksort files (the order must be preserved).

    $targetConfig['require'] = array_merge ($targetConfig['require'], $requires);
    if (!isset($targetConfig['autoload']))
      $targetConfig['autoload'] = [];
    $targetConfig['autoload']['psr-4'] = array_merge (get ($targetConfig['autoload'], 'psr-4', []), $psr4s);
    $targetConfig['autoload']['files'] = array_merge (get ($targetConfig['autoload'], 'files', []), $files);
    $targetConfig['bin']               = array_merge (get ($targetConfig, 'bin', []), $bins);
    $targetConfig['extra']             = array_merge (get ($targetConfig, 'extra', []), $extra);

    $currentConfig = file_get_contents ('composer.json');
    $targetCfgStr  = json_print ($targetConfig);
    if ($currentConfig == $targetCfgStr)
      $this->io->done ("<info>No changes</info> were made to composer.json");

    file_put_contents ('composer.json', $targetCfgStr);
    $this->io->writeln ("composer.json has been <info>updated</info>")->nl ();

    $this->doComposerUpdate ();

    $this->io->done ("The project is <info>updated</info>");
  }
}
