<?php
namespace Selenia;

use PhpCode;
use PhpKit\WebConsole\ErrorHandler;
use Selenia\Exceptions\Fatal\ConfigException;
use Selenia\Interfaces\AssignableInterface;
use Selenia\Traits\AssignableTrait;

class ModuleInfo implements AssignableInterface
{
  use AssignableTrait;

  const ref = __CLASS__;

  /**
   * A Unique Identifier for the module.
   * Plugins and Project Modules have names with 'vendor-name/package-name' syntax.
   * Subsystems have names with syntax: 'module-name'.
   * @var string
   */
  public $name;
  /**
   * The file system path of the module's root folder, relative to the project's root folder.
   * @var string
   */
  public $path;
  /**
   * An optional textual description (one line) of the module's purpose.
   * @var string
   */
  public $description;
  /**
   * When false, the module is ignored.
   * @var bool
   */
  public $enabled = true;
  /**
   * The module's service provider class name or null if none.
   * @var string|null
   */
  public $serviceProvider;

  /**
   * @global Application $application
   * @param string|null       $moduleName 'vendor/name'. If specified, the module will be searched for
   *                                 on the plugins path and on the project modules path,
   * @throws ConfigException
   */
  function __construct ($moduleName = null)
  {
    global $application;
    if ($moduleName) {
      $this->name  = $moduleName;
      $defaultPath = "$application->rootPath/$application->pluginModulesPath/$moduleName";
      $customPath  = "$application->rootPath/$application->modulesPath/$moduleName";
      if (file_exists ($customPath))
        $this->path = $customPath;
      elseif (file_exists ($defaultPath))
        $this->path = $defaultPath;
      else throw new ConfigException ("<p><b>Module not found.</b></p>
  <table>
  <tr><th>Name:         <td>$moduleName
  <tr><th>Default path: <td>" . ErrorHandler::shortFileName ($defaultPath) . "
  <tr><th>Extra path:   <td>" . ErrorHandler::shortFileName ($customPath) . "
  </table>");
    }
  }

  function loadRoutes ($configName = 'routes')
  {
    $path = "$this->path/config/$configName.php";
    $code = file_get_contents ($path, FILE_USE_INCLUDE_PATH);
    if ($code === false)
      throw new ConfigException("Can't load <b>$configName.php</b> on module <b>$this->name</b>.");
    $val = PhpCode::run ($code);
    if ($val === false)
      throw new ConfigException("Error on <b>$this->name</b>'s route-map definiton. Please check your PHP code.");
    return $val;
  }
}
