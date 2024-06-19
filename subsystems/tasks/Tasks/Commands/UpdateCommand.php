<?php

namespace Electro\Tasks\Commands;

use Electro\Interfaces\ConsoleIOInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Kernel\Services\ModulesRegistry;
use Electro\Tasks\Shared\Base\ComposerTask;

const BAT_TEMPLATE = <<<'CMD'
@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../___BIN_PATH___
php "%BIN_TARGET%" %*
CMD;

const BIN_TEMPLATE = <<<'BASH'
#!/usr/bin/env sh

dir=$(d=${0%[/\\]*}; cd "$d" > /dev/null; cd "../___BIN_DIR___" && pwd)

# See if we are running in Cygwin by checking for cygpath program
if command -v 'cygpath' >/dev/null 2>&1; then
	# Cygwin paths start with /cygdrive/ which will break windows PHP,
	# so we need to translate the dir path to windows format. However
	# we could be using cygwin PHP which does not require this, so we
	# test if the path to PHP starts with /cygdrive/ rather than /usr/bin
	if [[ $(which php) == /cygdrive/* ]]; then
		dir=$(cygpath -m "$dir");
	fi
fi

dir=$(echo $dir | sed 's/ /\ /g')
"${dir}/___BIN_FILE___" "$@"
BASH;

/**
 * Defines a command that rebuilds the project's composer.json file by merging relevant sections from the project
 * modules' composer.json files.
 * Whenever you modify a module's composer.json, you should run this command and then commit the changes to VCS.
 *
 * @property ModulesRegistry    $modulesRegistry
 * @property ConsoleIOInterface $io
 */

/**
 * @property KernelSettings $kernelSettings
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
      $this->task (ComposerTask::class)->action ('dump-autoload')->option ('--optimize')->run ();
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

    $requires     = $requiredBy = $psr4s = $bins = $files = $extra = [];
    $repositories = get ($targetConfig, 'repositories', []);

    $modules = $this->modulesRegistry->onlyPrivate ()->getModules ();

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

      // Merge 'repositories' section

      foreach ($config->get ('repositories', []) as $repo)
        if (is_null (array_find ($repositories, 'url', $repo['url'])))
          $repositories[] = $repo;
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
    if ($repositories)
      $targetConfig['repositories'] = $repositories;
    else unset ($targetConfig['repositories']);

    if ($bins)
      $this->publishBinaries ($bins);

    $currentConfig = file_get_contents ('composer.json');
    $targetCfgStr  = json_print ($targetConfig);
    if ($currentConfig == $targetCfgStr) {
      $this->io->done ("<info>No changes</info> were made to composer.json");
      return;
    }

    file_put_contents ('composer.json', $targetCfgStr);
  }

  private function publishBinaries (array $binaries)
  {
    $isWindows     = strtoupper (substr (PHP_OS, 0, 3)) === 'WIN';
    $baseDir       = $this->kernelSettings->baseDirectory;
    $publishingDir = "$baseDir/bin";
    foreach ($binaries as $binary) {
      $binFile       = basename ($binary);
      $binDir        = dirname ($binary);
      $publishedFile = "$publishingDir/$binFile";
      $pathToPublish = "$baseDir/$binary";
      if (file_exists ($publishedFile))
        unlink ($publishedFile);
      if (!$isWindows) {
        // On Mac or Linux use relative paths for symlinks.
        $relativeTarget = getRelativePath ("./$publishedFile", "./$pathToPublish");
        symlink ($relativeTarget, $publishedFile);
      }
      else {
        // Create Unix-style proxy file
        $script = str_replace (['___BIN_DIR___', '___BIN_FILE___'], [$binDir, $binFile], BIN_TEMPLATE);
        file_put_contents ($publishedFile, $script);

        // Create Windows batch file
        $script = str_replace ('___BIN_PATH___', $binary, BAT_TEMPLATE);
        file_put_contents ("$publishedFile.bat", $script);
      }
    }
  }

}
