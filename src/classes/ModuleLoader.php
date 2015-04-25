<?php

/**
 * Loads and runs modules.
 * It is usually used for responding to an HTTP request by instantiating the
 * corresponding controller class.
 */
class ModuleLoader
{

  static private $MIME_TYPES = [
    'js'    => 'application/javascript',
    'css'   => 'text/css',
    'woff'  => 'application/font-woff',
    'woff2' => 'application/font-woff2',
    'ttf'   => 'application/font-sfnt',
    'otf'   => 'application/font-sfnt',
    'jpg'   => 'image/jpeg',
    'png'   => 'image/png',
    'gif'   => 'image/gif',
  ];
  /**
   * Sitemap information for the current page.
   * @var PageRoute
   */
  public $sitePage = null;
  /**
   * The virtual URI following the question mark on the URL.
   * @var string
   */
  public $virtualURI = '';
  /**
   * The controller instance for the current module.
   * @var Controller
   */
  public $moduleInstance = null;
  /**
   * Assorted information on the module related to this loader.
   * @var ModuleInfo
   */
  public $moduleInfo = null;
  public static function loadAndRun ()
  {
    global $application, $loader, $lang;

    // Serve static assets exposed from packages or from the framework itself.

    $URI = $application->VURI;
    $p   = strpos ($URI, '/');
    if ($p) {
      $head = substr ($URI, 0, $p);
      $tail = substr ($URI, $p + 1);
      if (isset($application->mountPoints[$head])) {
        $path = $application->mountPoints[$head] . "/$tail";
        $type = get (self::$MIME_TYPES, substr ($tail, strrpos ($tail, '.') + 1), 'application/octet-stream');
        header ("Content-Type: $type");
        if (!$application->debugMode) {
          header ('Expires: ' . gmdate ('D, d M Y H:i:s \G\M\T', time () + 36000)); // add 10 hours
          header ("Cache-Control: public, max-age=36000");
        }
        readfile ($path);
        exit;
      }
    }

    // Load and execute the module that corresponds to the virtual URI.

    $loader = new ModuleLoader();
    $loader->init ();
    $loader->moduleInstance       = $loader->load ();
    $loader->moduleInstance->lang = $lang;
    $loader->moduleInstance->execute ();
  }
  /**
   * Loads the specified class.
   * @param string $className  The name of the class to be loaded (if not already loaded).
   * @param string $filePrefix An optional folder name.
   * @return boolean TRUE if the class was loaded.
   */
  public static function loadClass ($className, $filePrefix = '')
  {
    global $FRAMEWORK, $application;
    if (class_exists ($className))
      return true;
    if ($filePrefix != '' && substr ($filePrefix, -1) != '/')
      $filePrefix .= '/';
    $fname = "$filePrefix$className.php";
    if (!fileExists ($fname))
      return false;
    require_once $fname;
    return true;
  }
  /**
   *
   * @global Application $application
   * @param string       $className
   * @param string       $moduleName
   */
  public static function searchAndLoadClass ($className, $moduleName)
  {
    global $application;
    $p = strpos ($moduleName, '/services');
    if ($p !== false)
      $moduleName = substr ($moduleName, 0, $p);
    if (self::loadClass ($className, "$application->modulesPath/$moduleName/$application->modelPath"))
      return true;
    return self::loadClass ($className, "$application->defaultModulesPath/$moduleName/$application->modelPath");
  }
  /**
   * Initialize loader context.
   */
  public function init ()
  {
    global $application;

    //Find PageRoute info

    $this->virtualURI = $application->VURI;
    if ($this->virtualURI == '')
      $this->virtualURI = $application->defaultURI;
    $key = get ($_GET, 'key');
    if (!isset($application->routingMap))
      throw new ConfigException("No sitemap defined.");
    $this->sitePage = $application->routingMap->searchFor ($this->virtualURI, $key);
    if (is_null ($this->sitePage))
      Controller::pageNotFound ($this->virtualURI);

    //Setup preset parameters

    $presetParams = $this->sitePage->getPresetParameters ();
    $_REQUEST     = array_merge ($_REQUEST, (array)$presetParams);
    $_GET         = array_merge ($_GET, (array)$presetParams);

    //Setup module paths and other related info.

    $this->moduleInfo = new ModuleInfo($this->sitePage->module);
    if ($this->moduleInfo->isWebService)
      $application->setIncludePath ($this->moduleInfo->modulePath . PATH_SEPARATOR
                                    . dirname ($this->moduleInfo->modulePath));
    else $application->setIncludePath ($this->moduleInfo->modulePath);
  }
  /**
   * Load the module determined by the virtual URI.
   */
  public function load ()
  {
    if ($this->sitePage->autoController)
      $con = $this->moduleInfo->isWebService ? new WebServiceController : new Controller();
    else if (class_exists ($this->moduleInfo->pageClassName))
      $con = new $this->moduleInfo->pageClassName();
    else throw new FatalException("Auto-controller generation is disabled for this URL and the file <b>" .
                                  ErrorHandler::shortFileName ($this->moduleInfo->moduleFile) . "</b> was not found.");
    $con->moduleLoader = $this;
    return $con;
  }

}

