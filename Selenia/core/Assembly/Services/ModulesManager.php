<?php
namespace Selenia\Core\Assembly\Services;
use Exception;
use Selenia\Application;
use Selenia\Console\Contracts\ConsoleIOInterface;
use Selenia\Exceptions\Fatal\ConfigException;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Lib\ComposerConfigHandler;

/**
 * Provides an API for managing the application's modules.
 */
class ModulesManager
{
  const ref = __CLASS__;
  /**
   * @var Application
   */
  private $app;
  /**
   * @var ModulesRegistry
   */
  private $cachedRegistry;
  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * @var ModuleServices
   */
  private $moduleServices;

  function __construct (InjectorInterface $injector, Application $app, ModuleServices $moduleServices)
  {
    $this->app            = $app;
    $this->injector       = $injector;
    $this->moduleServices = $moduleServices;
  }

  /**
   * Initializes all modules.
   *
   * @throws ConfigException
   */
  function bootModules ()
  {
    /** @var ModuleInterface[] $providers */
    $providers = [];
    /** @var string[] $paths */
    $paths     = [];

    // Providers registration phase

    foreach ($this->registry ()->getAllModules () as $name => $module) {
      if ($module->enabled && $module->serviceProvider) {
        $provider = new $module->serviceProvider;

        if ($provider instanceof ServiceProviderInterface)
          $provider->register ($this->injector);

        if ($provider instanceof ModuleInterface) {
          $providers[] = $provider;
          $paths[]     = $module->path;
        }
      }
    }

    // Providers configuration phase

    foreach ($providers as $i => $provider) {
      $this->moduleServices->setPath ($paths[$i]);
      $provider->configure ($this->moduleServices);
    }
    $this->moduleServices->runPostConfig ();

    // Providers boot phase

    foreach ($providers as $provider) {
      $this->injector->execute ([$provider, 'boot']);
    }
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
   * Returns the modules registration configuration for this project.
   * If it is already cached, the cached version is returned, otherwise the information will be regenerated
   * and a new cache file created.
   * @return ModulesRegistry
   */
  function registry ()
  {
    if ($this->cachedRegistry)
      return $this->cachedRegistry;
    $registry = new ModulesRegistry($this->app);
    $registry->load ();
    return $this->cachedRegistry = $registry;
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
      if (!$this->registry ()->isInstalled ($moduleName))
        $io->error ("Module $moduleName is not installed");
    }
    else {
      $modules    = $this->registry ()->getApplicationModules ();
      $i          = $io->menu ("Select a module:", $modules);
      $moduleName = $modules[$i];
    }
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

  protected function getRegistryPath ()
  {
    return "{$this->app->modulesPath}/registry.json";
  }


}
