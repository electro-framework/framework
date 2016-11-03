<?php
namespace Electro\ViewEngine\Config;

use Electro\Interfaces\AssignableInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Kernel\Lib\ModuleInfo;
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
   * @var KernelSettings
   */
  private $kernelSettings;
  /**
   * @var string
   */
  private $moduleViewsPath = 'resources/views';
  /**
   * @var string[]
   */
  private $viewsDirectories = [];

  public function __construct (KernelSettings $kernelSettings)
  {
    $this->kernelSettings = $kernelSettings;
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
    array_unshift ($this->viewsDirectories, "{$this->kernelSettings->baseDirectory}/$moduleInfo->path/$this->moduleViewsPath");
    return $this;
  }

}
