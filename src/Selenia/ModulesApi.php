<?php
namespace Selenia;
use Exception;
use PhpKit\Flow\FilesystemFlow;
use Selenia\Contracts\ConsoleIOInterface;
use Selenia\Exceptions\ConfigException;
use Selenia\Lib\ComposerConfigHandler;
use Selenia\Lib\JsonFile;
use Selenia\Traits\Singleton;
use SplFileInfo;

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
    global $application; // Used by the loaded bootstrap.php
    $manifest = $this->manifest ();
    foreach ($manifest->modules as $module)
      includeFile ("{$module->path}/bootstrap.php");
  }

  /**
   * Returns the module's parsed composer.json, if it is present.
   * @param string $moduleName vendor-name/product-name
   * @return null|ComposerConfigHandler `null` if no composer.json is available.
   */
  function composerConfigOf ($moduleName)
  {
    $path           = $this->pathOf ($moduleName);
    $composerConfig = new ComposerConfigHandler("$path/composer.json", true);
    if (!$composerConfig->data)
      return null;
    return $composerConfig;
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
   * Returns the modules registration configuration for this project.
   * If it is already cached, the cached version is returned, otherwise the information will be regenerated
   * and a new cache file created.
   * @return mixed
   */
  function manifest ()
  {
    $json = new JsonFile ($this->getManifestPath ());
    return $json->exists ()
      ? $json->load ()->data
      : $json->assign ($this->getNewManifest ())->save ()->data;
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
    $namespace1 = ucfirst (dehyphenate ($vendor, true));
    $namespace2 = ucfirst (dehyphenate ($module, true));

    return "$namespace1\\$namespace2";
  }

  /**
   * Gets the names of all installed modules.
   * @return string[] Names in `vendor-name/package-name` syntax.
   */
  function moduleNames ()
  {
    $modules = array_merge ($this->pluginNames (), $this->projectModuleNames ());
    sort ($modules);
    return $modules;
  }

  /**
   * Returns information about all installed modules.
   *
   * Each module record defines:<dl>
   * <dt>name <dd>The module name (vendor/package).
   * <dt>path <dd>The full path of the module's root directory.
   * <dt>description <dd>A short one-liner describing the module.
   * <dt>type <dd>The type of module: Plugin | Project module.
   * </dl>
   * @return \StdClass[]
   */
  function modules ()
  {
    $modules = array_merge ($this->plugins (), $this->projectModules ());
    return flow ($modules)->map (function ($module) {
      $composerJson        = new JsonFile ("$module->path/composer.json");
      $module->description = $composerJson->exists ()
        ? $composerJson->load ()->get ('description')
        : '';
      $module->type        = $this->isPlugin ($module->name) ? 'Plugin' : 'Project module';
      return $module;
    })->all ();
  }

  /**
   * Retrieve the module's PHP namespace from its composer.json (if present).
   * @param string $moduleName vendor-name/product-name
   * @param string $srcPath    Outputs the source code path associated with the found namespace.
   * @return null|string `null` if no composer.json is available.
   * @throws Exception If the module's composer.json is not a valid module config.
   */
  function namespaceOf ($moduleName, & $srcPath = null)
  {
    $composerConfig = $this->composerConfigOf ($moduleName);
    $decls          = $composerConfig->get ("autoload.psr-4");
    $namespaces     = $decls ? array_keys ($decls) : [];
    if (count ($namespaces) != 1)
      throw new Exception ("Invalid module configuration for '$moduleName': expected a single PSR-4 namespace declaration on the module's composer.json");
    $namespace = $namespaces [0];
    $srcPath   = $decls[$namespace];
    return rtrim ($namespace, '\\');
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
    return array_getColumn ($this->plugins (), 'name');
  }

  function plugins ()
  {
    return FilesystemFlow
      ::from ("{$this->app->baseDirectory}/{$this->app->pluginModulesPath}")
      ->onlyDirectories ()
      ->expand (function (SplFileInfo $dirInfo) {
        return FilesystemFlow
          ::from ($dirInfo)
          ->onlyDirectories ()
          ->map (function (SplFileInfo $subDirInfo) use ($dirInfo) {
            return (object)[
              'name' => $dirInfo->getFilename () . '/' . $subDirInfo->getFilename (),
              'path' => $subDirInfo->getPathname (),
            ];
          });
      })
      ->all ();
  }

  /**
   * Gets the names of all local (non-plugin) modules.
   * @return string[] Names in `vendor-name/package-name` syntax.
   */
  function projectModuleNames ()
  {
    return array_getColumn ($this->projectModules (), 'name');
  }

  function projectModules ()
  {
    return FilesystemFlow
      ::from ("{$this->app->baseDirectory}/{$this->app->modulesPath}")
      ->onlyDirectories ()
      ->expand (function (SplFileInfo $dirInfo) {
        return FilesystemFlow
          ::from ($dirInfo)
          ->onlyDirectories ()
          ->map (function (SplFileInfo $subDirInfo) use ($dirInfo) {
            return (object)[
              'name' => $dirInfo->getFilename () . '/' . $subDirInfo->getFilename (),
              'path' => $subDirInfo->getPathname (),
            ];
          });
      })
      ->all ();
  }

  /**
   * Validate the given module name or ask the user to select a module from a list of installed modules.
   *
   * <p>This method is available to console tasks only.
   * @param string             $moduleName A variable reference. If empty, it will be set to the selected module name.
   * @param ConsoleIOInterface $io         Terminal input/output.
   */
  function selectModule (& $moduleName, ConsoleIOInterface $io)
  {
    if ($moduleName) {
      if (!$this->validateModuleName ($moduleName))
        $io->error ("Invalid module name $moduleName. Correct syntax: vendor-name/product-name");
      if (!$this->isInstalled ($moduleName))
        $io->error ("Module $moduleName is not installed");
    }
    else {
      $modules    = $this->moduleNames ();
      $i          = $io->menu ("Select a module:", $modules);
      $moduleName = $modules[$i];
    }
  }

  /**
   * Updates the manifest cache file so that it correctly states the currently installed modules.
   * @return object The updated manifest.
   */
  function updateManifest ()
  {
    unlink ($this->getManifestPath ());
    return $this->manifest ();
  }

  /**
   * Checks if the given name is a valid module name.
   * @param string $name A module name in `vendor-name/package-name` format.
   * @return bool `true` if the name is valid.
   */
  function validateModuleName ($name)
  {
    return (bool)preg_match ('#^[a-z0-9\-]+/[a-z0-9\-]+$#', $name);
  }

  protected function getManifestPath ()
  {
    return "{$this->app->modulesPath}/manifest.json";
  }

  protected function getNewManifest ()
  {
    return (object)[
      'modules' => $this->get ()->modules (),
    ];
  }

}
