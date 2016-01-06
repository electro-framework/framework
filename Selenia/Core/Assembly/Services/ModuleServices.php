<?php
namespace Selenia\Core\Assembly\Services;

use PhpKit\Flow\FilesystemFlow;
use Selenia\Application;
use Selenia\Exceptions\Fatal\ConfigException;
use Selenia\FileServer\Services\FileServerMappings;
use Selenia\Interfaces\AssignableInterface;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Interfaces\Http\Shared\ApplicationRouterInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationProviderInterface;

/**
 * Allows a module to notify the framework of which standard framework-specific services it provides (like routing,
 * navigation, translations, etc.).
 */
class ModuleServices
{
  /**
   * @var Application
   */
  private $app;
  /**
   * @var FileServerMappings
   */
  private $fileServerMappings;
  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * @var NavigationInterface
   */
  private $navigationInterface;
  /**
   * Stores temporarily the module path, for use by the other setters.
   * @var string
   */
  private $path;
  /**
   * A list of callbacks to be executed after all modules have performed their configuration.
   * @var callable[]
   */
  private $postConfigs = [];

  function __construct (Application $app,
                        InjectorInterface $injector)
  {
    $this->app      = $app;
    $this->injector = $injector;
  }

  private static function throwInvalidConfigType ($cfg)
  {
    throw new ConfigException(sprintf ("Unsupported configuration type: <kbd class=type>%s</kbd>",
      (is_object ($cfg) ? get_class ($cfg) : gettype ($cfg))));
  }

  /**
   * Sets a function that will be called after all modules have performed their configuration.
   * <p>It will be able to perform aditional configuration based on settings from other modules.
   * @param callable $fn
   */
  function onPostConfig (callable $fn)
  {
    $this->postConfigs[] = $fn;
  }

  /**
   * Does the module contains a macros directory?
   *
   * <p>If so, it is registered in the templating engine, along with any immediate sub-directories.
   *
   * @param boolean $v
   * @return $this
   */
  function provideMacros ($v = true)
  {
    if ($v) {
      $path = "$this->path/{$this->app->moduleMacrosPath}";
      $all  = FilesystemFlow::from ($path)->onlyDirectories ()->keys ()->all ();
      array_unshift ($all, $path);
      $this->app->macrosDirectories = array_merge ($all, $this->app->macrosDirectories);
    }
    return $this;
  }

  /**
   * Registers a navigation provider on the application.
   * @param NavigationProviderInterface $provider A class instance that provides a means to obtain a NavigationInterface
   * @return $this
   */
  function provideNavigation (NavigationProviderInterface $provider)
  {
    if ($this->app->isWebBased) {
      if (!$this->navigationInterface)
        $this->navigationInterface = $this->injector->make (NavigationInterface::class);

      $provider->defineNavigation ($this->navigationInterface);
    }
    return $this;
  }

  /**
   * @param boolean $v Does the module contains a translations folder?
   * @return $this
   */
  function provideTranslations ($v = true)
  {
    if ($v)
      $this->app->languageFolders[] = "$this->path/{$this->app->moduleLangPath}";
    return $this;
  }

  /**
   * @param boolean $v Does the module provide views on a views directory?
   * @return $this
   */
  function provideViews ($v = true)
  {
    if ($v)
      array_unshift ($this->app->viewsDirectories, "$this->path/{$this->app->moduleViewsPath}");
    return $this;
  }

  /**
   * @param array $v A map of URIs to folder paths. Paths are relative to the project's base folder.
   * @return $this
   */
  function publishDirs ($v)
  {
    if (!$this->fileServerMappings)
      $this->fileServerMappings = $this->injector->make (FileServerMappings::class);
    foreach ($v as $URI => $path)
      $this->fileServerMappings->map ($URI, "{$this->app->baseDirectory}/$path");
    return $this;
  }

  /**
   * @param string $v Published URI for the module's public folder.
   * @return $this
   */
  function publishPublicDirAs ($v)
  {
    if (!$this->fileServerMappings)
      $this->fileServerMappings = $this->injector->make (FileServerMappings::class);
    $this->fileServerMappings->map ($v, "$this->path/{$this->app->modulePublicPath}");
    return $this;
  }

  /**
   * A list of relative file paths of assets published by the module, relative to the module's public folder.
   * The framework's build process may automatically concatenate and minify those assets for a release-grade build.
   * @param string[] $v
   * @return $this
   */
  function registerAssets ($v)
  {
    if ($v)
      array_mergeInto ($this->app->assets, array_map (function ($path) {
        return "$this->path/$path";
      }, $v));
    return $this;
  }

  /**
   * @param array $v Map of tag names to component classes.
   * @return $this
   */
  function registerComponents (array $v)
  {
    array_mergeInto ($this->app->tags, $v);
    return $this;
  }

  /**
   * @param string[] $v List of class names providing component presets.
   * @return $this
   */
  function registerPresets (array $v)
  {
    array_mergeInto ($this->app->presets, $v);
    return $this;
  }

  /**
   * Registers a router on the application.
   *
   * > **Note:** both `RouterInterface` and `MiddlewareStackInterface` are compatible with
   * `RequestHandlerInterface`.
   *
   * @param string|callable|RequestHandlerInterface $handler A request handler instance or class name.
   * @param string|int|null                         $key     An ordinal index or an arbitrary identifier to associate
   *                                                         with the given handler.
   *                                                         <p>If not specified, an auto-incrementing integer index
   *                                                         will be assigned.
   *                                                         <p>If an integer is specified, it may cause the handler to
   *                                                         overwrite an existing handler at the same ordinal position
   *                                                         on the pipeline.
   *                                                         <p>String keys allow you to insert new handlers after a
   *                                                         specific one.
   *                                                         <p>Some MiddlewareStackInterface implementations
   *                                                         may use the key for other purposes (ex. route matching
   *                                                         patterns).
   * @param string|int|null                         $after   Insert after an existing handler that lies at the given
   *                                                         index, or that has the given key. When null, it is
   *                                                         appended.
   * @return $this
   */
  function registerRouter ($handler, $key = null, $after = null)
  {
    // $router is not injected because it's retrieval must be postponed until after the routing module loads.
    /** @var ApplicationRouterInterface $router */
    if ($this->app->isWebBased) {
      $router = $this->injector->make (ApplicationRouterInterface::class);
      $router->add ($handler, $key, $after);
    }
    return $this;
  }

  /**
   * @param string $v Name of the module's class that implements the module's tasks.
   * @return $this
   */
  function registerTasksFromClass ($v)
  {
    $this->app->taskClasses[] = $v;
    return $this;
  }

  /**
   * Runs all the previously registered post config handlers.
   * > **Reserved** for internal use by the framework.
   */
  function runPostConfig ()
  {
    foreach ($this->postConfigs as $cfg)
      $cfg ();
  }

  /**
   * @param array $v Configuration default settings to be merged into the application config.
   *                 Settings already defined will not be changed, except for Application settings.
   * @return $this
   * @throws ConfigException
   */
  function setDefaultConfig ($v)
  {
    foreach ($v as $section => $cfg) {
      if ($section == 'main') {
        foreach ($cfg as $k => $v)
          $this->app->$k = $v;
      }
      else {
        $appCfg = get ($this->app->config, $section);
        if (is_null ($appCfg)) {
          $this->app->config[$section] = $cfg;
          continue;
        }
        elseif (is_array ($appCfg)) {
          if (is_array ($cfg))
            $appCfg = $appCfg + $cfg;
          else if ($cfg instanceof AssignableInterface)
            $appCfg = $appCfg + $cfg->_export ();
          else self::throwInvalidConfigType ($cfg);
        }
        elseif ($appCfg instanceof AssignableInterface)
          $appCfg->_defaults ($cfg);
        else self::throwInvalidConfigType ($cfg);
        $this->app->config[$section] = $appCfg;
      }
    }
    return $this;
  }

  /**
   * Sets the root directory of the module, from where other relative directory paths will be computed.
   * > **You do not need to call this!**
   *
   * It is used internally by the framework, but you can also use it if you want to relocate some of the module's
   * internal directories into non-standard locations.
   * @param string $path
   * @return $this
   */
  function setPath ($path)
  {
    $this->path = $path;
    return $this;
  }

}
