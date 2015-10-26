<?php
namespace Selenia\Assembly\Traits;

use Selenia\Application;
use Selenia\Exceptions\Fatal\ConfigException;
use Selenia\Interfaces\AssignableInterface;
use Selenia\Interfaces\InjectorInterface;

trait ModuleOptionsTrait
{
  public $path;

  /**
   * Sets the 
   * @param string $path
   * @return $this
   */
  function path ($path)
  {
    $this->path = $path;
    return $this;
  }

  /**
   * Returns an array with keys for each class property name and values that define the property data type.
   * @return array
   */
  public function getTypes ()
  {
    return [
      'templates'  => 'boolean',
      'views'      => 'boolean',
      'public'     => 'string',
      'publish'    => 'array',
      'lang'       => 'boolean',
      'assets'     => 'array',
      'config'     => 'array',
      'components' => 'array',
      'presets'    => 'array',
      'routes'     => 'array',
      'tasks'      => 'string',
    ];
  }

  /**
   * A list of relative file paths of assets published by the module, relative to the module's public folder.
   * The framework's build process may automatically concatenate and minify those assets for a release-grade build.
   * @param string[] $v
   */
  function set_assets ($v)
  {
    global $application;
    if ($v)
      $application->assets = array_merge ($application->assets, array_map (function ($path) {
        return "$this->path/$path";
      }, $v));
  }

  /**
   * @param array $v Map of tag names to componenbt classes.
   */
  function set_components (array $v)
  {
    Application::$TAGS = array_merge (Application::$TAGS, $v);
  }

  /**
   * @param array $v Configuration default settings to be merged into the application config.
   *                 Settings already defined will not be changed.
   * @throws ConfigException
   */
  function set_config ($v)
  {
    global $application;
    foreach ($v as $section => $cfg) {
      if ($section == 'main') {
        foreach ($cfg as $k => $v)
          if (!property ($application, $k)) $application->$k = $v;
      }
      else {
        $appCfg = get ($application->config, $section);
        if (is_null ($appCfg)) {
          $application->config[$section] = $cfg;
          continue;
        }
        elseif (is_array ($appCfg)) {
          if (is_array ($cfg))
            $appCfg = $appCfg + $cfg;
          else if ($cfg instanceof AssignableInterface)
            $appCfg = $appCfg + $cfg->export ();
          else self::throwInvalidConfigType ($cfg);
        }
        elseif ($appCfg instanceof AssignableInterface)
          $appCfg->defaults ($cfg);
        else self::throwInvalidConfigType ($cfg);
        $application->config[$section] = $appCfg;
      }
    }
  }

  /**
   * @param boolean $v Does the module contains a translations folder?
   */
  function set_lang ($v)
  {
    global $application;
    if ($v)
      $application->languageFolders[] = "$this->path/$application->moduleLangPath";
  }

  /**
   * @param string[] $v List of class names providing component presets.
   */
  function set_presets (array $v)
  {
    global $application;
    $application->presets = array_merge ($v, $application->presets);
  }

  /**
   * @param string $v Published URI for the module's public folder.
   */
  function set_public ($v)
  {
    global $application;
    $application->mount ($v, "$this->path/$application->modulePublicPath");
  }

  /**
   * @param array $v A map of URIs to folder paths. Paths are relative to the project's base folder.
   */
  function set_publish ($v)
  {
    global $application;
    foreach ($v as $URI => $path)
      $application->mount ($URI, "$application->baseDirectory/$path");
  }

  /**
   * @param array $v Optional preset routes for the module.
   */
  function set_routes (array $v)
  {
    global $application;
    $application->routes = array_merge ($v, $application->routes);
  }

  /**
   * @param string $v Name of the module's class that implements the module's tasks.
   */
  function set_tasks ($v)
  {
    global $application;
    $application->taskClasses[] = $v;
  }

  /**
   * @param boolean $v Does the module contains a templates directory?
   */
  function set_templates ($v)
  {
    global $application;
    if ($v)
      array_unshift ($application->templateDirectories, "$this->path/$application->moduleTemplatesPath");
  }

  function set_views ($v)
  {
    global $application;
    if ($v)
      array_unshift ($application->viewsDirectories, "$this->path/$application->moduleViewsPath");
  }

}
