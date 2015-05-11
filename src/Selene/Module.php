<?php
namespace Selene;

use Selene\Exceptions\ConfigException;

class Module
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
    else throw new ConfigException ("Module not found:<p><b>$moduleName</b>");
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