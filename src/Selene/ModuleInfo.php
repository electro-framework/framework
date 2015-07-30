<?php
namespace Selene;

use Impactwave\WebConsole\ErrorHandler;
use Selene\Exceptions\ConfigException;

class ModuleInfo
{

  /**
   * The folder path for the current module, relative to the base modules directory.
   * This acts as a Uniform Resource Identifier for the module.
   * @var string
   */
  public $name;

  /**
   * The file system path of the module's root folder.
   * @var string
   */
  public $path;

  /**
   * @global Application $application
   * @param string       $moduleName 'vendor/name'
   * @throws ConfigException
   */
  function __construct ($moduleName)
  {
    global $application;
    $this->name  = $moduleName;
    $defaultPath = "$application->rootPath/$application->defaultModulesPath/$moduleName";
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

  function loadRoutes ($configName = 'routes')
  {
    $path = "$this->path/config/$configName.php";
    $code = file_get_contents ($path, FILE_USE_INCLUDE_PATH);
    if ($code === false)
      throw new ConfigException("Can't load <b>$configName.php</b> on module <b>$this->name</b>.");
    $val = evalPHP ($code);
    if ($val === false)
      throw new ConfigException("Error on <b>$this->name</b>'s route-map definiton. Please check your PHP code.");
    return $val;
  }
}
