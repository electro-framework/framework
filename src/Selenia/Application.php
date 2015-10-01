<?php
namespace Selenia;

use Exception;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use PhpKit\WebConsole\ConsolePanel;
use PhpKit\WebConsole\ErrorHandler;
use PhpKit\WebConsole\Panels\HttpRequestPanel;
use PhpKit\WebConsole\WebConsole;
use PhpKit\WebConsole\WebConsoleLogHandler;
use Selenia\Exceptions\ConfigException;
use Selenia\Exceptions\HttpException;
use Selenia\Matisse\PipeHandler;
use Selenia\Routing\RoutingMap;

define ('CONSOLE_ALIGN_CENTER', STR_PAD_BOTH);
define ('CONSOLE_ALIGN_LEFT', STR_PAD_RIGHT);
define ('CONSOLE_ALIGN_RIGHT', STR_PAD_LEFT);

class Application
{
  const DEFAULT_INI_FILENAME = 'application.defaults.ini.php';

  public static $TAGS = [];
  /**
   * Holds an array of SEO infomation for each site page or null;
   * @var array
   */
  public $SEOInfo;
  /**
   * The URI of current application's directory.
   * @var string
   */
  public $URI;
  /**
   * The address of the page to be displayed when the current page URI is invalid.
   * If null an exception is thrown.
   * @var string
   */
  public $URINotFoundURL = null;
  /**
   * The virtual URI specified after the application's base URI.
   * @var string
   */
  public $VURI;
  public $addonsPath;
  /**
   * The real application name.
   * @var string
   */
  public $appName;
  /**
   * A list of relative file paths of assets published by each module, relative to each module's public folder, in
   * order of precedence. The framework's build process may automatically concatenate and minify those assets for a
   * release-grade build.
   * @var string[]
   */
  public $assets = [];
  /**
   * The class to be instantiated when creating an automatic controller.
   * @var string
   */
  public $autoControllerClass;
  public $autoSession = false;
  /**
   * The file path of current main application's root directory.
   * @var string
   */
  public $baseDirectory;
  /**
   * The URI of current main application's root directory.
   * @var string
   */
  public $baseURI;
  public $cachePath;
  public $condenseLiterals;
  /**
   * Configuration settings for registered modules.
   * Each key is that name of a module and its value is the configuration array of that module.
   * @var array
   */
  public $config;
  /**
   * The name of the file that contains the application's configuration settings.
   * @var string
   */
  public $configFilename;
  /**
   * Folder path for the configuration files.
   * @var string
   */
  public $configPath;
  /**
   * This is set only when running the console Task Runner.
   * @var \Symfony\Component\Console\Application
   */
  public $console;
  /**
   * Holds an array of multiple DataSourceInfo for each site page or null;
   * @var array
   */
  public $dataSources;
  public $debugMode;
  /**
   * A two letter code for default site language. NULL if i18n is disabled.
   * <p>This is set on the environment (ex: .env).
   * @var string
   */
  public $defaultLang = null;
  /**
   * The file path of current application's directory.
   * @var string
   */
  public $directory;
  public $enableCompression;
  /**
   * Favorite icon URL.
   * @var string
   */
  public $favicon = '';
  public $fileArchivePath;
  /**
   * The path of the framework kernel's directory.
   * @var string
   */
  public $frameworkPath;
  /**
   * @var Boolean True to generate the standard framework scripts.
   */
  public $frameworkScripts;

  /* Template related */
  /**
   * The mapped public URI of the framework's public directory.
   * @var string
   */
  public $frameworkURI;
  /**
   * Set to false to disable application-specific sessions and use a global scope.
   * @var Boolean
   */
  public $globalSessions = false;

  /* Archive related */
  /**
   * The homepage's breadcrumb icon class(es).
   * @var string
   */
  public $homeIcon;
  /**
   * The homepage's breadcrumb title.
   * @var string
   */
  public $homeTitle;
  /**
   * The application's entry point URI.
   *
   * It is also the default URI to redirect to when none is specified on the URL.
   * The URI locates an entry on the routing map where additional info. is used to
   * load the default page.
   * Set by application.ini.php
   * @var String
   */
  public $homeURI;

  /* Cache related */
  public $imageArchivePath;
  /**
   * Set to true to redirect the browser to the generated thumbnail instead of streaming it.
   * @var Boolean
   */
  public $imageRedirection;

  /* Page processing control settings */
  public $imagesCachePath;
  /**
   * The colon delimited list of directory paths.
   * @var string
   */
  public $includePath;
  /**
   * The path of the application's language files' folder.
   * @var string
   */
  public $langPath;
  /**
   * Search paths for module language files, in order of precedence.
   * @var array
   */
  public $languageFolders = [];
  /**
   * List of languages enabled on the application.
   *
   * <p>Each language should be specified like this: `langCode:ISOCode:langLabel:locale1|locale2`
   *
   * <p>Ex.
   * ```
   * [
   *   'en:en-US:English:en_US|en_US.UTF-8|us',
   *   'pt:pt-PT:Português:pt_PT|pt_PT.UTF-8|ptg',
   *   'es:es-ES:Español:es_ES|es_ES.UTF-8|esp'
   * ]
   * ```
   * @var string[]
   */
  public $languages = [];
  /**
   * A list of logger handler instances to push to the logger's handlers stack.
   * Set on application.ini
   * @var HandlerInterface[]
   */
  public $logHandlers = [];
  /**
   * The application's main logger.
   * @var Logger
   */
  public $logger;
  /**
   * Relative file path of the view to be used for authenticating the user.
   * <p>It will be searched for on both the active module and on the application.
   * @var string
   */
  public $loginView;
  public $modelPath;
  /**
   * The relative path of the language files' folder inside a module.
   * @var string
   */
  public $moduleLangPath;
  /**
   * The relative path of the public folder inside a module.
   * @var string
   */
  public $modulePublicPath;
  /**
   * The relative path of the templates folder inside a module.
   * @var string
   */
  public $moduleTemplatesPath;
  /**
   * The relative path of the views folder inside a module.
   * @var string
   */
  public $moduleViewsPath;

  /* Session related */
  /**
   * A list of modules that are always bootstrapped when the framework boots.
   * <p>A `bootstrap.php` file will be executed on each registered module.
   * @var array
   */
  public $modules;
  /**
   * The folder where the framework will search for your application-specific modules.
   * <p>If a module is not found there, it will then search on `defaultModulesPath`.
   * <p>Set by application.ini.php.
   * @var String
   */
  public $modulesPath;
  /**
   * A map of mappings from virtual URIs to external folders.
   * <p>This is used to expose assets from composer packages.
   * <p>Array of URI => physical folder path
   * @var array
   */
  public $mountPoints = [];
  /**
   * The application name.
   * This should be composed only of alphanumeric characters. It is used as the session name.
   * If not specified, defaults to the parent application name or, if not defined, the application path or,
   * if it is a root application, the server name.
   * @var string
   */
  public $name;
  /**
   * Maximum width and/or height for uploaded images.
   * Images exceeding this dimensions are resized to fit them.
   * @var int
   */
  public $originalImageMaxSize;
  /**
   * JPEG compression factor for resampled uploaded images.
   * @var int
   */
  public $originalImageQuality;
  /**
   * The URL parameter name used for pagination.
   * @var string
   */
  public $pageNumberParam;
  /**
   * The default page size for the default data source.
   * @var number
   */
  public $pageSize;
  public $pageTemplate;
  /**
   * @var PipeHandler
   */
  public $pipeHandler;
  /**
   * <p>The fallback folder name where the framework will search for modules.
   * <p>Plugin modules installed as Composer packages will be found there.
   * <p>Set by application.ini.php.
   * @var String
   */
  public $pluginModulesPath;
  /**
   * @var string[] A list of "preset" class names.
   */
  public $presets = [];
  public $requireLogin;
  public $rootPath;
  /**
   * @var array
   */
  public $routes = [];
  /**
   * The application'a routing map.
   * @var RoutingMap
   */
  public $routingMap;
  public $storagePath;
  /**
   * A map of URI prefixes to application configuration files.
   * @var array
   */
  public $subApplications;
  /**
   * A list of task classes from each module that provides tasks to be merged on the main robofile.
   * @var string[]
   */
  public $taskClasses = [];
  /**
   * Directories where templates can be found.
   * <p>They will be search in order until the requested template is found.
   * <p>These paths will be registered on the templating engine.
   * <p>This is preinitialized to the application template's path.
   * @var array
   */
  public $templateDirectories = [];
  public $templatesPath;
  /**
   * A site name that can be used on autogenerated window titles (using the title tag).
   * @var string
   */
  public $title;
  /**
   * Enables output post-processing for keyword replacement.
   * Disable this if the app is not multi-language to speed-up page rendering.
   * Keywords syntax: $keyword
   * @var bool
   */
  public $translation = false;
  /**
   * The FQN of the logged in user's model class.
   * @var string
   */
  public $userModel;
  public $viewPath;
  /**
   * Folders where views can be found.
   * <p>They will be search in order until the requested view is found.
   * @var array
   */
  public $viewsDirectories = [];

  function exceptionHandler (Exception $e)
  {
    if (function_exists ('database_rollback'))
      database_rollback ();
    if ($this->logger)
      $this->logger->error($e->getMessage(), ['stackTrace' => str_replace("$this->baseDirectory/", '', $e->getTraceAsString())]);
    WebConsole::outputContent (true);
  }

  function fromPathToURL ($path)
  {
    return $this->toURL ($this->toURI ($path));
  }

  /**
   * Given a theme's stylesheet or CSS URI this method returns an unique name
   * suitable for naming a file on the cache folder.
   * @param String $URI The absolute URI of the original file.
   * @return String A file name.
   */
  function generateCacheFilename ($URI)
  {
    $themesPath = strpos ($URI, $this->themesPath) !== false ? $this->themesPath : $this->defaultThemesPath;
    return str_replace ('/', '_', substr ($URI, strlen ($this->baseURI) + strlen ($themesPath) + 2));
  }

  function getAddonURI ($addonName)
  {
    return "$this->baseURI/$this->addonsPath/$addonName";
  }

  function getFileDownloadURI ($fileId)
  {
    return "$this->frameworkURI/download?id=$fileId";
  }

  function getFileURI ($fileName)
  {
    return "$this->baseURI/$this->fileArchivePath/$fileName";
  }

  function getImageDownloadURI ($fileId)
  {
    return "$this->frameworkURI/image?id=$fileId";
  }

  function getImageURI ($fileName)
  {
    return "$this->baseURI/$this->imageArchivePath/$fileName";
  }

  function initDOMPanel (Controller $controller)
  {
    if (isset($controller->page)) {
      $insp = $controller->page->inspect (true);
      WebConsole::DOM ()->write ($insp);
//      $filter = function ($k, $v) { return $k !== 'parent' && $k !== 'page'; };
//      WebConsole::DOM ()->withFilter($filter, $controller->page);
      WebConsole::vm ()->log ($controller->context->dataSources);
    }
  }

  /**
   * Composer packages can call this method to expose assets on web.
   * @param string $URI
   * @param string $path
   */
  function mount ($URI, $path)
  {
    $this->mountPoints[$URI] = $path;
  }

  /**
   * @param string $rootDir
   */
  function run ($rootDir)
  {
    global $session;
    set_exception_handler ([$this, 'exceptionHandler']);
    $this->debugMode = $_SERVER['APP_DEBUG'] == 'true';

    ErrorHandler::init ($this->debugMode, $rootDir);
    $this->setupWebConsole ();
    $this->setup ($rootDir);
    $this->initSession ();
    ModulesApi::get ()->bootModules ();
    $this->mount ($this->frameworkURI, $this->frameworkPath . DIRECTORY_SEPARATOR . $this->modulePublicPath);
    $this->registerPipes ();
    if ($this->debugMode) {
      WebConsole::config ($this);
      WebConsole::session ()
                ->write ('<button type="button" class="__btn __btn-default" style="position:absolute;right:5px;top:5px" onclick="__doAction(\'logout\')">Log out</button>')
                ->log ($session);
      $this->logger->pushHandler (new WebConsoleLogHandler(WebConsole::log ()));
    }
    $this->loadRoutes ();
    try {
      $router = Router::route ();
    } catch (HttpException $e) {
      @ob_get_clean ();
      http_response_code ($e->getCode ());
      echo $e->getMessage ();
      exit;
    }
    $router->controller->execute ();
    if ($this->debugMode) {
      $filter = function ($k, $v) { return $k !== 'parent' || is_null ($v) ?: '...'; };
      WebConsole::routes ()->withCaption ('Active Route')->withFilter ($filter, $router->activeRoute);
      WebConsole::response (['Content-Length' => round (ob_get_length () / 1024) . ' KB']);
    }
    if (!$router->controller->isWebService)
      WebConsole::outputContent ();
  }

  function setIncludePath ($extra = '')
  {
    if (!empty($extra)) {
      $extra .= PATH_SEPARATOR;
      set_include_path ($this->includePath = $extra . $this->includePath);
      return;
    }
    $path = $extra . $this->rootPath;
    set_include_path ($path);
    $this->includePath = $path;
  }

  /**
   * Sets up the application configuration.
   * When overriding this method, always call the super() after running your own
   * code, so that paths computed here can take into account your changes.
   * @param string $rootDir
   * @throws ConfigException
   */
  function setup ($rootDir)
  {
    $uri     = get ($_SERVER, 'REQUEST_URI');
    $baseURI = dirnameEx (get ($_SERVER, 'SCRIPT_NAME'));
    $vuri    = substr ($uri, strlen ($baseURI) + 1) ?: '';
    if (($p = strpos ($vuri, '?')) !== false)
      $vuri = substr ($vuri, 0, $p);

    $this->requireLogin  = false;
    $this->directory     = $rootDir;
    $this->baseDirectory = $rootDir;
    $this->rootPath      = $rootDir;
    $this->URI           = $baseURI;
    $this->baseURI       = $baseURI;
    $this->frameworkPath = updir (__DIR__, 2);
    $this->VURI          = $vuri;

    $this->setIncludePath ();
    $this->loadConfiguration ($vuri);

//    $this->templateDirectories[] = $this->toFilePath ($this->templatesPath);
//    $this->viewsDirectories[]    = $this->toFilePath ($this->viewPath);
    $this->languageFolders[] = $this->langPath;
    if (isset($_ENV['APP_DEFAULT_LANG']))
      $this->defaultLang = $_ENV['APP_DEFAULT_LANG'];
    $this->logger = new Logger('main', $this->logHandlers);
  }

  function toFilePath ($URI, &$isMapped = false)
  {
    $p = strpos ($URI, '/');
    if ($p) {
      $head = substr ($URI, 0, $p);
      if ($head == 'modules') {
        $p    = strpos ($URI, '/', $p + 1);
        $p    = strpos ($URI, '/', $p + 1);
        $head = substr ($URI, 0, $p);
      }
      $tail = substr ($URI, $p + 1);
      if (isset($this->mountPoints[$head])) {
        if (func_num_args () == 2)
          $isMapped = true;
        $path = $this->mountPoints[$head] . "/$tail";
        return $path;
      }
    }
    return "$this->baseDirectory/$URI";
  }

  /**
   * Strips the base path from the given absolute path if it falls lies inside the applicatiohn.
   * Otherwise, it returns the given path unmodified.
   * @param string $path
   * @return string
   */
  function toRelativePath ($path)
  {
    if ($path) {
      if ($path[0] == DIRECTORY_SEPARATOR) {
        $l = strlen ($this->baseDirectory);
        if (substr ($path, 0, $l) == $this->baseDirectory)
          return substr ($path, $l + 1);
      }
    }
    return $path;
  }

  function toThemeURI ($relativeURI, Theme &$theme)
  {
    return "$this->baseURI/$theme->path/$relativeURI";
  }

  function toURI ($path)
  {
    return "$this->baseURI/$path";
  }

  function toURL ($URI)
  {
    $port = ':' . $_SERVER['SERVER_PORT'];
    if ($port == ":80")
      $port = '';
    return "http://{$_SERVER['SERVER_NAME']}$port$URI";
  }

  protected function initSession ()
  {
    global $session;
    if (!$this->globalSessions)
      session_name ($this->name);
    $name = session_name ();
    session_start ();
    if ($this->autoSession) {
      $session                 = get ($_SESSION, 'sessionInfo', new Session);
      $session->name           = $name;
      $_SESSION['sessionInfo'] = $session;
    }
  }

  protected function loadConfiguration ($vuri)
  {
    $_ = DIRECTORY_SEPARATOR;

    // Load default configuration.

    $iniPath = "$this->frameworkPath{$_}src{$_}" . self::DEFAULT_INI_FILENAME;
    $this->loadConfig ($iniPath);

    // Load application-specific configuration.

    $iniPath = "$this->rootPath{$_}$this->configPath{$_}$this->configFilename";
    $this->loadConfig ($iniPath);

    foreach ($this->subApplications as $prefix => $path) {
      if (substr ($vuri, 0, strlen ($prefix)) == $prefix) {
        $iniPath = "$this->rootPath{$_}$this->configPath{$_}$path";
        $this->loadConfig ($iniPath);
      }
    }
  }

  protected function registerPipes ()
  {
    $this->pipeHandler = new PipeHandler;
    $this->pipeHandler->registerPipes (new DefaultPipes);
  }

  protected function setupWebConsole ()
  {
    WebConsole::init ($this->debugMode);
    WebConsole::registerPanel ('request', new HttpRequestPanel ('Request', 'fa fa-paper-plane'));
    WebConsole::registerPanel ('response', new ConsolePanel ('Response', 'fa fa-file'));
    WebConsole::registerPanel ('routes', new ConsolePanel ('Routes', 'fa fa-location-arrow'));
    WebConsole::registerPanel ('config', new ConsolePanel ('Config.', 'fa fa-cogs'));
    WebConsole::registerPanel ('session', new ConsolePanel ('Session', 'fa fa-user'));
    WebConsole::registerPanel ('DOM', new ConsolePanel ('DOM', 'fa fa-sitemap'));
    WebConsole::registerPanel ('vm', new ConsolePanel ('View Models', 'fa fa-table'));
    WebConsole::registerPanel ('database', new ConsolePanel ('Database', 'fa fa-database'));
//    WebConsole::registerPanel ('exceptions', new ConsolePanel ('Exceptions', 'fa fa-bug'));
    ErrorHandler::$appName = 'Selenia framework';
  }

  private function loadConfig ($iniPath)
  {
    $ini = @include $iniPath;
    if ($ini) {
      extend ($this, $ini['main']);
      unset ($ini['main']);
    }
    else
      throw new ConfigException("Error parsing " . ErrorHandler::shortFileName ($iniPath));
    $this->config = $ini;
  }

  private function loadRoutes ()
  {
    $map         = $this->routingMap = new RoutingMap();
    $map->routes = array_merge ($map->routes, $this->routes);
    $map->init ();

    if ($this->debugMode) {
      $filter = function ($k, $v) { return $k !== 'parent' || is_null ($v) ?: '...'; };
      WebConsole::routes ()->withFilter ($filter, $this->routingMap->routes);
    }
  }

}
