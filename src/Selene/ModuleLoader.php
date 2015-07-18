<?php
namespace Selene;
use Impactwave\WebConsole\ErrorHandler;
use Selene\Exceptions\ConfigException;
use Selene\Routing\PageRoute;

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

    $URI  = $application->VURI;
    $path = $application->toFilePath ($URI, $isMapped);
    if ($isMapped) {
      if (file_exists ($x = "$path.php")) {
        require $x;
        exit;
      }
      $type = get (self::$MIME_TYPES, substr ($path, strrpos ($path, '.') + 1), 'application/octet-stream');
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
      exit; // The file has been sent, so stop here.
    }

    // Load and execute the module that corresponds to the virtual URI.

    $loader = new ModuleLoader();
    $loader->init ();
    $loader->moduleInstance       = $loader->load ();
    $loader->moduleInstance->lang = $lang;
    $loader->moduleInstance->execute ();
    return $loader;
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
      $this->virtualURI = $application->homeURI;
    $key = get ($_GET, 'key');
    if (!isset($application->routingMap))
      throw new ConfigException("No route map defined.");
    $this->sitePage = $application->routingMap->searchFor ($this->virtualURI, $key);
    if (is_null ($this->sitePage)) {
      if (strpos (get($_SERVER, 'HTTP_ACCEPT'), 'text/html') !== false)
        Controller::pageNotFound ($this->virtualURI);
      else {
        header ("Content-Type: text/plain");
        http_response_code (404);
      }
    }

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
    /** @var Controller $con */
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
      throw new ConfigException("<p><b>Controller not found.</b></p>
  <table>
  <tr><th>Class:                  <td>$class
  <tr><th>Additional search path: <td>" . ErrorHandler::shortFileName ($this->moduleInfo->path) . "
  <tr><th>Auto-controller:        <td><i>$auto</i> for this URL
  </table>
");
    }
    $con->moduleLoader = $this;
    return $con;
  }

}

