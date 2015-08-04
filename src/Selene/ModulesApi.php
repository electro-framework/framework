<?php
namespace Selene;
use Selene\Exceptions\ConfigException;
use Selene\Traits\Singleton;
use Selene\Util\FilesystemQuery;

/**
 * Provides an API for querying module information.
 */
class ModulesApi
{
  use Singleton;

  private $app;

  function __construct (Application $app)
  {
    $this->app = $app;
  }

  /**
   * @throws ConfigException
   */
  function bootModules ()
  {
    var_dump (ModulesApi::get ()->pluginNames ());
    exit;
    return;
    global $application; // Used by the loaded bootstrap.php

    foreach ($this->pluginNames () as $plugin)
      includeFile ("{$this->app->pluginModulesPath}/$plugin/bootstrap.php");

    foreach ($this->localModuleNames () as $module)
      includeFile ("{$this->app->pluginModulesPath}/$module/bootstrap.php");
  }

  /**
   * Checks if a module is installed, either as a plugin or as a local module, by verifying its existence on disk.
   * @param string $moduleName `vendor-name/package-name` syntax.
   * @return bool
   */
  function isInstalled ($moduleName)
  {
    return $this->pathOf ($moduleName) !== false;
  }

  /**
   * Checks if the installed module with the given name is a plugin.
   * @param string $moduleName `vendor-name/package-name` syntax.
   * @return bool
   */
  function isPlugin ($moduleName)
  {
    return file_exists ("{$this->app->pluginModulesPath}/$moduleName");
  }

  /**
   * Gets the names of all local (non-plugin) modules.
   * @return string[] Names in `vendor-name/package-name` syntax.
   */
  function localModuleNames ()
  {
    $o          = [];
    $pluginsDir = dir ($this->app->modulesPath);
    foreach ($pluginsDir as $dir) {
      $vendorDir = dir ("{$this->app->modulesPath}/$dir");
      foreach ($vendorDir as $subdir)
        $o[] = "$dir/$subdir";
    }
    return $o;
  }

  /**
   * Converts a module name in `vendor-name/package-name` form to a valid PSR-4 namespace.
   * @param string $moduleName
   * @return string
   */
  function moduleNameToNamespace ($moduleName)
  {
    $o = explode ('/', $moduleName);
    if (count ($o) != 2)
      throw new \RuntimeException ("Invalid module name");
    list ($vendor, $module) = $o;
    $namespace1 = ucfirst (dehyphenate ($vendor));
    $namespace2 = ucfirst (dehyphenate ($module));

    return "$namespace1\\$namespace2";
  }

  /**
   * Gets the names of all installed modules.
   * @return string[] Names in `vendor-name/package-name` syntax.
   */
  function moduleNames ()
  {
    return array_merge ($this->localModuleNames (), $this->pluginNames ());
  }

  /**
   * Returns the directory path where the specified module is installed.
   * @param string $moduleName `vendor-name/package-name` syntax.
   * @return bool|string The path or `false` if the module is not installed.
   */
  function pathOf ($moduleName)
  {
    $path = "{$this->app->pluginModulesPath}/$moduleName";
    if (file_exists ($path)) return $path;
    $path = "{$this->app->modulesPath}/$moduleName";
    if (file_exists ($path)) return $path;
    return false;
  }

  /**
   * Gets the names of all modules installed as plugins.
   * @return string[] Names in `vendor-name/package-name` syntax.
   */
  function pluginNames ()
  {
    $base = "{$this->app->baseDirectory}/{$this->app->pluginModulesPath}";
    $r    = FilesystemQuery::scanDir ($base)
                           ->onlyDirectories ()
                           ->expand (function ($v, $k) {
                             echo "$v\n";
                             return FilesystemQuery::scanDir ($v)->onlyDirectories ();
                           })
                           ->map (function (\SplFileInfo $f) {
                             return $f->getPathname ();
                           })
                           ->all ();
    echo "\n";
    var_dump ($r);
    exit;
    $pluginsDir = dir ($base);
    foreach ($pluginsDir as $dir) {
      _log ($dir);
      $vendorDir = dir ("{$this->app->pluginModulesPath}/$dir");
      foreach ($vendorDir as $subdir)
        $o[] = "$dir/$subdir";
    }
    return $o;
  }

}
