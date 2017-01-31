<?php

namespace Electro\ViewEngine\Config;

use Electro\Interfaces\AssignableInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Traits\ConfigurationTrait;

/**
 * Configuration settings for the ViewEngine subsystem.
 *
 * @method $this|string moduleViewsPath (string $v = null) The relative path of the views folder inside a module
 * @method $this|bool caching (bool $v = null) When TRUE, view rendering is accelerated by caching compiled templates
 */
class ViewEngineSettings implements AssignableInterface
{
  use ConfigurationTrait;

  /** @var bool */
  private $caching = true;
  /** @var string */
  private $moduleViewsPath = 'resources/views';
  /** @var array */
  private $viewModelNamespaces = [];
  /** @var string[] */
  private $viewsDirectories = [];

  /**
   * Returns a list of all directories where views can be found.
   * <p>They will be search in order until the requested view is found.
   *
   * @return string[]
   */
  function getDirectories ()
  {
    return $this->viewsDirectories;
  }

  /**
   * Returns a mapping between modules view templates base directories and the corresponding PHP namespaces that will be
   * used for resolving view template paths to PHP controller classes.
   *
   * @return array
   */
  function getViewModelNamespaces ()
  {
    return $this->viewModelNamespaces;
  }

  /**
   * Registers a mapping between relative view paths and the given PHP namespace.<br>
   * It will be used for resolving PHP ViewModel classes from view template paths.
   *
   * <p>The ViewService will search all defined mappings, in the reverse order in which they were defined, to
   * instantiate the correct ViewModel class with which to render a template that is about to be rendered.
   * <br> If no suitable mapping is found, no data will be given to the view renderer engine.
   *
   * @param string $namespace The root namespace for the View Model classes.
   * @return $this
   */
  function registerViewModelsNamespace ($namespace)
  {
    array_unshift ($this->viewModelNamespaces, $namespace);
    return $this;
  }

  /**
   * Registers the module's views directory as a source for loading templates.
   *
   * @param ModuleInfo $moduleInfo
   * @return $this
   */
  function registerViews (ModuleInfo $moduleInfo)
  {
    $path = "$moduleInfo->path/$this->moduleViewsPath";
    array_unshift ($this->viewsDirectories, $path);
    return $this;
  }
}
