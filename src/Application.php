<?php
namespace Electro;

use Electro\Exceptions\Fatal\ConfigException;
use Electro\Interfaces\DI\InjectorInterface;

class Application
{
  const FRAMEWORK_PATH = 'private/packages/electro/framework';
  /**
   * The real application name. This is read from templates.
   *
   * @var string
   */
  public $appName = '⚡️ ELECTRO';
  /**
   * The file path of current main application's root directory.
   *
   * @var string
   */
  public $baseDirectory;
  /**
   * The URI of current main application's root directory.
   *
   * @var string
   */
  public $baseURI;
  /**
   * @var bool The value set on the .env file for the DEBUG variable.
   */
  public $debugMode = false;
  /**
   * Favorite icon URL. This is read from templates.
   *
   * @var string
   */
  public $favicon = 'data:;base64,iVBORw0KGgo=';
  /**
   * The path of the framework kernel's directory.
   *
   * @var string
   */
  public $frameworkPath;
  /**
   * The mapped public URI of the framework's public directory.
   *
   * @var string
   */
  public $frameworkURI = 'framework';
  /**
   * @var \Electro\Core\DependencyInjection\Injector
   */
  public $injector;
  /**
   * If `true` the application is a console app, otherwise it may be a web app.
   *
   * @see \Electro\Application::$isWebBased
   * @var bool
   */
  public $isConsoleBased = false;
  /**
   * If `true` the application is a web app, otherwise it may be a console app.
   *
   * @see \Electro\Application::$isConsoleBased
   * @var bool
   */
  public $isWebBased = false;
  /**
   * The relative path of the public folder inside a module.
   *
   * @var string
   */
  public $modulePublicPath = 'public';
  /**
   * The folder where the framework will search for your application-specific modules.
   * <p>If a module is not found there, it will then search on `defaultModulesPath`.
   * <p>Set by application.ini.php.
   *
   * @var String
   */
  public $modulesPath = 'private/modules';
  /**
   * The path to the folder where symlinks to all modules' public folders are placeed.
   *
   * @var string
   */
  public $modulesPublishingPath = 'modules';
  /**
   * The application name.
   * This should be composed only of alphanumeric characters. It is used as the session name.
   *
   * @var string
   */
  public $name = 'electro';
  /**
   * The URL parameter name used for pagination.
   *
   * @var string
   */
  public $pageNumberParam = 'p';
  /**
   * The default page size for pagination (ex: on the DataGrid). It is only applicable when the user has not yet
   * selected a custom page size.
   *
   * @var number
   */
  public $pageSize = 15;
  /**
   * <p>The fallback folder name where the framework will search for modules.
   * <p>Plugin modules installed as Composer packages will be found there.
   * <p>Set by application.ini.php.
   *
   * @var String
   */
  public $pluginModulesPath = 'private/plugins';
  /**
   * @var string The file path of a router script for the build-in PHP web server.
   */
  public $routerFile = 'private/packages/electro/framework/devServerRouter.php';
  /**
   * @var string
   */
  public $storagePath = 'private/storage';
  /**
   * A site name that can be used on auto-generated window titles (using the title tag).
   * The symbol @ will be replaced by the current page's title.
   *
   * @var string
   */
  public $title = '@';

  function __construct (InjectorInterface $injector)
  {
    $this->injector = $injector;
  }

  /**
   * Returns the directory where bootloaders for each known profile are stored.
   *
   * @return string
   */
  function getBootloadersPath ()
  {
    return "$this->storagePath/boot";
  }

  /**
   * Gets an array of file path mappings for the core framework, to aid debugging symlinked directiories.
   *
   * @return array
   */
  function getMainPathMap ()
  {
    $rp = realpath ($this->frameworkPath);
    return $rp != $this->frameworkPath ? [
      $rp => self::FRAMEWORK_PATH,
    ] : [];
  }

  /**
   * Sets up the application configuration.
   * When overriding this method, always call the super() after running your own
   * code, so that paths computed here can take into account your changes.
   *
   * @param string $rootDir
   * @throws ConfigException
   */
  function setup ($rootDir)
  {
    // Note: due to eventual symlinking, we can't use dirname(__DIR__) here
    $this->baseDirectory = $rootDir;
    $this->frameworkPath = self::FRAMEWORK_PATH;

    // Setup the include path by prepending the application's root and the framework's to it.
    // Note: the include path is used, for instance, by stream_resolve_include_path() or file_exists()

//    $dir = $rootDir . PATH_SEPARATOR . "$rootDir/" . self::FRAMEWORK_PATH;
    $path = get_include_path ();
    $path = substr ($path, 0, 2) == '.' . PATH_SEPARATOR
      ? $rootDir . substr ($path, 1)       // replace the '.' directory by $rootDir
      : $rootDir . PATH_SEPARATOR . $path; // otherwise prepend $rootDir to the path
    set_include_path ($path);
  }

  /**
   * Strips the base path from the given absolute path if it falls lies inside the applicatiohn.
   * Otherwise, it returns the given path unmodified.
   *
   * @param string $path
   * @return string
   */
  function toRelativePath ($path)
  {
    if ($path) {
      if ($path[0] == DIRECTORY_SEPARATOR || $path[1] == ':') {
        $l = strlen ($this->baseDirectory);
        if (substr ($path, 0, $l) == $this->baseDirectory)
          return substr ($path, $l + 1);
      }
    }
    return $path;
  }

}
