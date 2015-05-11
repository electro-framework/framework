<?php
namespace Selene;
use Selene\Exceptions\ConfigException;

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
    'ttf'   => 'font/ttf',
    'otf'   => 'font/otf',
    'eot'   => 'application/vnd.ms-fontobject',
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
   * @var Module
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
      if ($head == 'modules') {
        $p    = strpos ($URI, '/', $p + 1);
        $head = substr ($URI, 0, $p);
      }
      $tail = substr ($URI, $p + 1);
      if (isset($application->mountPoints[$head])) {
        $path = $application->mountPoints[$head] . "/$tail";
        $type = get (self::$MIME_TYPES, substr ($tail, strrpos ($tail, '.') + 1), 'application/octet-stream');
        header ("Content-Type: $type");
        if (!$application->debugMode) {
          header ('Expires: ' . gmdate ('D, d M Y H:i:s \G\M\T', time () + 36000)); // add 10 hours
          header ("Cache-Control: public, max-age=36000");
        }
        if (@readfile ($path) === false) {
          header ("Content-Type: text/plain");
          http_response_code (404);
          echo "Not found: $path";
        }
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
      throw new ConfigException("No route map defined.");
    $this->sitePage = $application->routingMap->searchFor ($this->virtualURI, $key);
    if (is_null ($this->sitePage))
      Controller::pageNotFound ($this->virtualURI);

    //Setup preset parameters

    $presetParams = $this->sitePage->getPresetParameters ();
    $_REQUEST     = array_merge ($_REQUEST, (array)$presetParams);
    $_GET         = array_merge ($_GET, (array)$presetParams);

    //Setup module paths and other related info.
    $this->moduleInfo = new Module($this->sitePage->module);
    $application->setIncludePath ($this->moduleInfo->path);
  }

  /**
   * Load the module determined by the virtual URI.
   */
  public function load ()
  {
    global $application;
    $con  = null;
    $auto = $this->sitePage->autoController;
    if ($auto) {
      if (!empty($this->sitePage->controller))
        throw new ConfigException("<p><b>A controller should not be specified when autoController is enabled.</b>
<p>Hint: is autoController=true being inherited?");
      $class = $application->autoControllerClass;
      if (class_exists ($class))
        $con = new $class;
    }
    else {
      $class = $this->sitePage->controller;
      if (class_exists ($class))
        $con = new $class;
    }
    if (!$con) {
      if (!$class)
        $class = "null";
      $auto = $auto ? 'enabled' : 'disabled';
      throw new ConfigException("<p><b>Controller not found.</b>
  <li>Class:           <b>$class</b>
  <li>Search path:     <b>" . ErrorHandler::shortFileName ($this->moduleInfo->path) . "</b>
  <li>Auto-controller: <b>$auto</b> for this URL
");
    }
    $con->moduleLoader = $this;
    return $con;
  }

}

