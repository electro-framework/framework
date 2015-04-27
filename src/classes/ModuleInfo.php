<?php

class ModuleInfo
{

  /**
   * The folder path for the current module, relative to the base modules directory.
   * This acts as a Uniform Resource Identifier for the module.
   * @var string
   */
  public $module;

  /**
   * The file system path of the module's root folder.
   * @var string
   */
  public $modulePath;

  /**
   * @global Application $application
   * @param string       $moduleName
   * @throws FatalException
   */
  public function __construct ($moduleName)
  {
    global $application;
    $this->module = $moduleName;
    $defaultPath = "$application->rootPath/$application->defaultModulesPath/$this->module";
    $customPath  = "$application->rootPath/$application->modulesPath/$this->module";
    if (file_exists ($customPath))
      $this->modulePath = $customPath;
    elseif (file_exists ($defaultPath))
      $this->modulePath = $defaultPath;
    else throw new FatalException ("Module not found:<p><b>$moduleName</b>");
  }
}