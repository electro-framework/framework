<?php
namespace Selenia\Routing;
use PhpKit\WebConsole\ErrorHandler;
use Selenia\Core\Assembly\ModuleInfo;
use Selenia\Exceptions\Fatal\ConfigException;
use Selenia\Exceptions\HttpException;
use Selenia\Http\Controllers\Controller;

/**
 * Routes the current URI to the corresponding controller.
 *
 * It is usually used for responding to an HTTP request by instantiating the
 * corresponding controller class.
 *
 * It also serves static assets from module's public directories.
 */
class Router
{

  /**
   * The route thar matches the current URI.
   * @var PageRoute
   */
  public $activeRoute = null;
  /**
   * The controller instance for the current module.
   * @var Controller
   */
  public $controller = null;
  /**
   * Assorted information on the module related to the active controller.
   * @var ModuleInfo
   */
  public $moduleInfo = null;
  /**
   * The virtual URI following the question mark on the URL.
   * @var string
   */
  public $virtualURI = '';

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
    $this->activeRoute = $application->routingMap->searchFor ($this->virtualURI, $key);
    if (is_null ($this->activeRoute))
      return;

    //Setup preset parameters

    $presetParams = $this->activeRoute->getPresetParameters ();
    $_REQUEST     = array_merge ($_REQUEST, (array)$presetParams);
    $_GET         = array_merge ($_GET, (array)$presetParams);

    //Setup module paths and other related info.
    $this->moduleInfo = new ModuleInfo ($this->activeRoute->module);
    $application->setIncludePath ($this->moduleInfo->path);
  }

  /**
   * Load the module determined by the virtual URI.
   */
  public function route ()
  {
    global $application;

    if (is_null ($this->activeRoute))
      return;

    /** @var Controller $con */
    $con  = null;
    $auto = $this->activeRoute->autoController;
    if ($auto) {
      if (!empty($this->activeRoute->controller))
        throw new ConfigException("<p><b>A controller should not be specified when autoController is enabled.</b>
<p>Hint: is autoController=true being inherited?");
      $class = $application->autoControllerClass;
      if (class_exists ($class))
        $con = $class;
    }
    else {
      $class = $this->activeRoute->controller;
      if (class_exists ($class))
        $con = $class;
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
    return $con;
  }

}

