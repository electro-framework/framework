<?php

class ModuleInfo
{

  /**
   * The base name for the current module.
   * @var string
   */
  public $module;

  /**
   * The page name of the page within the current module.
   * @var string
   */
  public $modulePage;

  /**
   * The folder path for the current module, relative to the base modules directory.
   * This acts as a Uniform Resource Identifier for the module.
   * @var string
   */
  public $URI;

  /**
   * The file system path for the module controller.
   * @var string
   */
  public $moduleFile;

  /**
   * The base controller class for all pages in this module.
   * @var string
   */
  public $moduleClassName;

  /**
   * The page controller class name.
   * @var string
   */
  public $pageClassName;

  /**
   * Indicates if the module is a WebService.
   * @var boolean
   */
  public $isWebService = false;

  /**
   * @global Application $application
   * @param string       $moduleURI
   * @throws FatalException
   */
  public function __construct ($moduleURI)
  {
    global $application, $isWebService;
    $this->URI    = $moduleURI;
    $this->module = dirname ($moduleURI);
    if ($this->module == '.') {
      $this->module     = $moduleURI;
      $this->modulePage = '';
    }
    else $this->modulePage = substr ($moduleURI, strrpos ($moduleURI, '/') + 1);
    $defaultPath = "$application->rootPath/$application->defaultModulesPath/$this->module";
    $customPath  = "$application->rootPath/$application->modulesPath/$this->module";
    if (file_exists ($customPath))
      $this->modulePath = $customPath;
    elseif (file_exists ($defaultPath))
      $this->modulePath = $defaultPath;
    else throw new FatalException ("Module not found:<p><b>$moduleURI</b>");
    if ($isWebService || substr ($this->module, -9) == '/services')
      $this->isWebService = true;
    $this->moduleFile      = $this->modulePath . '/' . $this->modulePage . '.php';
    $p                     = strrpos ($this->module, '/');
    $module                = $p !== false ? substr ($this->module, $p + 1) : $this->module;
    $this->moduleClassName = $module . 'Controller';
    $this->pageClassName   = ucfirst ($this->modulePage) . 'Controller';
  }
}