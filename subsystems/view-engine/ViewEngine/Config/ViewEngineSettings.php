<?php
namespace Electro\ViewEngine\Config;

use Electro\Application;
use Electro\Core\Assembly\ModuleInfo;
use Electro\Interfaces\AssignableInterface;
use Electro\Traits\ConfigurationTrait;

/**
 * Configuration settings for the Tasks subsystem.
 *
 * @method $this|string moduleViewsPath (string $v = null) The relative path of the views folder inside a module
 */
class ViewEngineSettings implements AssignableInterface
{
  use ConfigurationTrait;

  /**
   * @var Application
   */
  private $app;
  /**
   * @var string
   */
  private $moduleViewsPath = 'resources/views';
  /**
   * @var string[]
   */
  private $viewsDirectories = [];

  public function __construct (Application $app)
  {
    $this->app = $app;
  }

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
   * Registers a module's views from its views directory.
   *
   * @param ModuleInfo $moduleInfo
   * @return $this
   */
  function registerViews (ModuleInfo $moduleInfo)
  {
    array_unshift ($this->viewsDirectories, "{$this->app->baseDirectory}/$moduleInfo->path/$this->moduleViewsPath");
    return $this;
  }

}
