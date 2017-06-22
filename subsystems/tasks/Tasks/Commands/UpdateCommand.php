<?php

namespace Electro\Tasks\Commands;

use Electro\Interfaces\ConsoleIOInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Kernel\Services\ModulesRegistry;
use Electro\Tasks\Shared\Base\ComposerTask;

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
   *
   * @param array $opts
   * @option $no-update When set, no package will be modified, only the autoloader will be updated
   */
  function composerUpdate ($opts = ['no-update' => false])
  {
    $this->regenerateComposer ();
    $this->io->writeln ("composer.json has been <info>updated</info>")->nl ()->write ('- ');

    if (get ($opts, 'no-update'))
      (new ComposerTask)->action('dump-autoload')->option('--optimize')->run ();
    else $this->doComposerUpdate ();

    $this->io->done ("The project is <info>updated</info>");
  }

  /**
   * Regenerates the root composer.json file.
   */
  protected function regenerateComposer ()
  {
    // We MUST regenerate the module registry now, so that the `getModules()` call below will pick up the current
    // modules, NOT the previously registered ones.
    $this->moduleRefresh ();

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

    $files = array_merge (get ($targetConfig['autoload'], 'files', []), $files);
    $bins  = array_merge (get ($targetConfig, 'bin', []), $bins);
    $extra = array_merge (get ($targetConfig, 'extra', []), $extra);

    if ($files)
      $targetConfig['autoload']['files'] = $files;
    else unset ($targetConfig['autoload']['files']);
    if ($bins)
      $targetConfig['bin'] = $bins;
    else unset ($targetConfig['bin']);
    if ($extra)
      $targetConfig['extra'] = $extra;
    else unset ($targetConfig['extra']);

    $currentConfig = file_get_contents ('composer.json');
    $targetCfgStr  = json_print ($targetConfig);
    if ($currentConfig == $targetCfgStr) {
      $this->io->done ("<info>No changes</info> were made to composer.json");
      return;
    }

    file_put_contents ('composer.json', $targetCfgStr);
  }

}
