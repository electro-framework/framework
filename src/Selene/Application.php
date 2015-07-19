<?php
namespace Selene;

use Exception;
use Impactwave\WebConsole\ConsolePanel;
use Impactwave\WebConsole\ErrorHandler;
use Impactwave\WebConsole\Panels\HttpRequestPanel;
use Impactwave\WebConsole\WebConsole;
use Selene\Exceptions\ConfigException;
use Selene\Matisse\PipeHandler;
use Selene\Routing\RoutingMap;

class Application
{
  const INI_FILENAME         = 'application.ini.php';
  const DEFAULT_INI_FILENAME = 'application.defaults.ini.php';
  const FRAMEWORK_PATH       = 'private/packages/selene-framework/selene-kernel';

  public static $TAGS = [];

  /**
   * The application name.
   * This should be composed only of alphanumeric characters. It is used as the session name.
   * If not specified, defaults to the parent application name or, if not defined, the application path or,
   * if it is a root application, the server name.
   * @var string
   */
  public $name;
  /**
   * The real application name.
   * @var string
   */
  public $appName;
  /**
   * A site name that can be used on autogenerated window titles (using the title tag).
   * @var string
   */
  public $title;
  public $rootPath;
  /**
   * The URI of current application's directory.
   * @var string
   */
  public $URI;
  /**
   * The file path of current application's directory.
   * @var string
   */
  public $directory;
  /**
   * The URI of current main application's root directory.
   * @var string
   */
  public $baseURI;
  /**
   * The file path of current main application's root directory.
   * @var string
   */
  public $baseDirectory;
  /**
   * The colon delimited list of directory paths.
   * @var string
   */
  public $includePath;
  /**
   * The path of the framework kernel's directory.
   * @var string
   */
  public $frameworkPath;
  /**
   * The path of the framework's preset scaffolds's directory, relative to the kernel's directory.
   *
   * This is used by the task runner.
   * @var string
   */
  public $scaffoldsPath;
  /**
   * The virtual URI specified after the application's base URI.
   * @var string
   */
  public $VURI;
  /**
   * The relative path of the public folder inside a module.
   * @var string
   */
  public $modulePublicPath;
  /**
   * The mapped public URI of the framework's public directory.
   * @var string
   */
  public $frameworkURI;
  public $modelPath;
  public $viewPath;
  /**
   * The relative path of the views folder inside a module.
   * @var string
   */
  public $moduleViewsPath;
  /**
   * The relative path of the templates folder inside a module.
   * @var string
   */
  public $moduleTemplatesPath;
  public $addonsPath;
  /**
   * The path of the application's language files' folder.
   * @var string
   */
  public $langPath;
  /**
   * The relative path of the language files' folder inside a module.
   * @var string
   */
  public $moduleLangPath;
  /**
   * The folder where the framework will search for your application-specific modules.
   * <p>If a module is not found there, it will then search on `defaultModulesPath`.
   * <p>Set by application.ini.php.
   * @var String
   */
  public $modulesPath;
  /**
   * <p>The fallback folder name where the framework will search for modules.
   * <p>Plugin mmdules installed as Composer packages will be found there.
   * <p>Set by application.ini.php.
   * @var String
   */
  public $defaultModulesPath;
  /**
   * A list of modules that are always bootstrapped when the framework boots.
   * <p>A `bootstrap.php` file will be executed on each registered module.
   * @var array
   */
  public $modules;
  /**
   * Folder path for the configuration files.
   * @var string
   */
  public $configPath;
  /**
   * Relative file path of the view to be used for authenticating the user.
   * <p>It will be searched for on both the active module and on the application.
   * @var string
   */
  public $loginView;
  /**
   * The class name of the application's robo tasks.
   * @var string
   */
  public $tasksClass;
  /**
   * The FQN of the logged in user's model class.
   * @var string
   */
  public $userModel;

  /* Template related */
  public $templatesPath;
  public $pageTemplate;

  /* Archive related */
  public $storagePath;
  public $imageArchivePath;
  public $fileArchivePath;

  /* Cache related */
  public $cachePath;
  public $imagesCachePath;

  /* Page processing control settings */
  public $enableCompression;
  public $debugMode;
  public $condenseLiterals;
  public $packScripts;
  public $packCSS;
  public $resourceCaching;
  /**
   * @var Boolean True to generate the standard framework scripts.
   */
  public $frameworkScripts;
  /**
   * Defines the file path for the Model collection or its XML description, set on application.ini.php.
   * @var String
   */
  public $modelFile;
  /**
   * Defines the file path for the data sources collection or its XML description, set on application.ini.php.
   * @var String
   */
  public $dataSourcesFile;
  /**
   * Defines the file path for the SEO information collection or its XML description, set on application.ini.php.
   * @var String
   */
  public $SEOFile;
  /**
   * The class to be instantiated when creating an automatic controller.
   * @var string
   */
  public $autoControllerClass;
  /**
   * The application'a routing map.
   * @var RoutingMap
   */
  public $routingMap;
  /**
   * A map of URI prefixes to application configuration files.
   * @var array
   */
  public $subApplications;
  /**
   * Holds an array of multiple DataSourceInfo for each site page or null;
   * @var array
   */
  public $dataSources;
  /**
   * Holds an array of SEO infomation for each site page or null;
   * @var array
   */
  public $SEOInfo;
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
  /**
   * The address of the page to be displayed when the current page URI is invalid.
   * If null an exception is thrown.
   * @var string
   */
  public $URINotFoundURL = null;

  /* Session related */
  public $isSessionRequired;
  public $autoSession = false;
  /**
   * Set to false to disable application-specific sessions and use a global scope.
   * @var Boolean
   */
  public $globalSessions = false;

  /**
   * Favorite icon URL.
   * @var string
   */
  public $favicon = '';

  /**
   * Set to true to redirect the browser to the generated thumbnail instead of streaming it.
   * @var Boolean
   */
  public $imageRedirection;

  /**
   * Enables output post-processing for keyword replacement.
   * Disable this if the app is not multi-language to speed-up page rendering.
   * Keywords syntax: $keyword
   * @var bool
   */
  public $translation = false;
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
   * A two letter code for default site language. NULL if i18n is disabled.
   * <p>This is set on the environment (ex: .env).
   * @var string
   */
  public $defaultLang = null;

  /**
   * The default page size for the default data source.
   * @var number
   */
  public $pageSize;

  /**
   * The URL parameter name used for pagination.
   * @var string
   */
  public $pageNumberParam;

  /**
   * Define a message to be displayed if the browser is IE6.
   * If not set or empty, no message is shown.
   * For multilingual sites, the text may be a $XXX translation code.
   * @var string
   */
  public $oldIEWarning;

  /**
   * If set, this defines the public IP of the production server hosting the release website.
   * This will be used to check if the website is running on the production webserver.
   * @see Controller->isProductionSite
   * @var string
   */
  public $productionIP;

  /**
   * Defines the Google Anallytics account ID.
   * This is required if the GoogleAnalytics template is present on the page.
   * @var string
   */
  public $googleAnalyticsAccount;
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
   * A map of mappings from virtual URIs to external folders.
   * <p>This is used to expose assets from composer packages.
   * <p>Array of URI => physical folder path
   * @var array
   */
  public $mountPoints = [];
  /**
   * Directories where templates can be found.
   * <p>They will be search in order until the requested template is found.
   * <p>These paths will be registered on the templating engine.
   * <p>This is preinitialized to the application template's path.
   * @var array
   */
  public $templateDirectories = [];
  /**
   * Folders where views can be found.
   * <p>They will be search in order until the requested view is found.
   * @var array
   */
  public $viewsDirectories = [];
  /**
   * Search paths for module language files, in order of precedence.
   * @var array
   */
  public $languageFolders = [];
  /**
   * Extended application configuration.
   * <p>Key 'main' has the configuration of the Application class.
   * <p>Other keys may hold module-specific configs.
   * @var array
   */
  public $config;
  /**
   * @var string[] A list of "preset" class names.
   */
  public $presets = [];
  /**
   * @var PipeHandler
   */
  public $pipeHandler;
  /**
   * @var array
   */
  public $routes = [];

  static function exceptionHandler (Exception $e)
  {
    if (function_exists ('database_rollback'))
      database_rollback ();
    WebConsole::outputContent (true);
  }

  /**
   * @param string $rootDir
   */
  public function run ($rootDir)
  {
    global $session;
    set_exception_handler ([get_class (), 'exceptionHandler']);
    $this->debugMode = $_SERVER['APP_DEBUG'] == 'true';

    ErrorHandler::init ($this->debugMode, $rootDir);
    $this->setupWebConsole ();
    $this->setup ($rootDir);
    $this->initSession ();
    $this->registerPipes ();
    if ($this->debugMode) {
      WebConsole::config ($this);
      WebConsole::session ()
                ->write ('<button type="button" class="__btn __btn-default" style="position:absolute;right:5px;top:5px" onclick="__doAction(\'logout\')">Log out</button>')
                ->log ($session);
    }
    $this->loadRoutes ();
    $loader = ModuleLoader::loadAndRun ();
    if ($this->debugMode) {
      $filter = function ($k, $v) { return $k !== 'parent' || is_null ($v) ?: '...'; };
      WebConsole::routes ()->withCaption ('Active Route')->withFilter ($filter, $loader->sitePage);
      WebConsole::response (['Content-Length' => round (ob_get_length () / 1024) . ' KB']);
    }
    if (!$loader->moduleInstance->isWebService)
      WebConsole::outputContent ();
  }

  /**
   * Composer packages can call this method to expose assets on web.
   * @param string $URI
   * @param string $path
   */
  public function mount ($URI, $path)
  {
    $this->mountPoints[$URI] = $path;
  }

  /**
   * Sets up the application configuration.
   * When overriding this method, always call the super() after running your own
   * code, so that paths computed here can take into account your changes.
   * @param string $rootDir
   * @throws ConfigException
   */
  public function setup ($rootDir)
  {
    $_       = DIRECTORY_SEPARATOR;
    $uri     = get ($_SERVER, 'REQUEST_URI');
    $baseURI = dirnameEx (get ($_SERVER, 'SCRIPT_NAME'));
    $vuri    = substr ($uri, strlen ($baseURI) + 1) ?: '';
    if (($p = strpos ($vuri, '?')) !== false)
      $vuri = substr ($vuri, 0, $p);

    $this->isSessionRequired = false;
    $this->directory         = $rootDir;
    $this->baseDirectory     = $rootDir;
    $this->rootPath          = $rootDir;
    $this->URI               = $baseURI;
    $this->baseURI           = $baseURI;
    $this->frameworkPath     = realpath ("$rootDir{$_}" . self::FRAMEWORK_PATH);
    $this->VURI              = $vuri;

    $this->setIncludePath ();

    // Load default configuration.

    $iniPath = "$this->frameworkPath{$_}src{$_}" . self::DEFAULT_INI_FILENAME;
    $this->loadConfig ($iniPath);

    // Load application-specific configuration.

    $iniPath = "$this->rootPath{$_}$this->configPath{$_}" . self::INI_FILENAME;
    $this->loadConfig ($iniPath);

    foreach ($this->subApplications as $prefix => $path) {
      if (substr ($vuri, 0, strlen ($prefix)) == $prefix) {
        $iniPath = "$this->rootPath{$_}$this->configPath{$_}$path";
        $this->loadConfig ($iniPath);
      }
    }

    $this->templateDirectories[] = $this->toFilePath ($this->templatesPath);
    $this->viewsDirectories[]    = $this->toFilePath ($this->viewPath);
    $this->languageFolders[]     = $this->langPath;
    $this->bootModules ();

    if (empty($this->name))
      $this->name = $this->URI ? $this->URI : $_SERVER['SERVER_NAME'];
    if (isset($_ENV['APP_DEFAULT_LANG']))
      $this->defaultLang = $_ENV['APP_DEFAULT_LANG'];

    $this->mount ($this->frameworkURI, "$this->frameworkPath{$_}$this->modulePublicPath");
  }

  public function setIncludePath ($extra = '')
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

  public function toURL ($URI)
  {
    $port = ':' . $_SERVER['SERVER_PORT'];
    if ($port == ":80")
      $port = '';
    return "http://{$_SERVER['SERVER_NAME']}$port$URI";
  }

  public function toURI ($path)
  {
    return "$this->baseURI/$path";
  }

  public function fromPathToURL ($path)
  {
    return $this->toURL ($this->toURI ($path));
  }

  public function toFilePath ($URI, &$isMapped = false)
  {
    $p   = strpos ($URI, '/');
    if ($p) {
      $head = substr ($URI, 0, $p);
      if ($head == 'modules') {
        $p    = strpos ($URI, '/', $p + 1);
        $head = substr ($URI, 0, $p);
      }
      $tail = substr ($URI, $p + 1);
      if (isset($this->mountPoints[$head])) {
        if (func_num_args() == 2)
          $isMapped = true;
        $path = $this->mountPoints[$head] . "/$tail";
        return $path;
      }
    }
    return "$this->baseDirectory/$URI";
  }

  public function toRelativePath ($URI)
  {
    global $application;
    return substr ($URI, strlen ($application->baseURI) + 1);
  }

  public function toThemeURI ($relativeURI, Theme &$theme)
  {
    return "$this->baseURI/$theme->path/$relativeURI";
  }

  public function getAddonURI ($addonName)
  {
    return "$this->baseURI/$this->addonsPath/$addonName";
  }

  public function getImageURI ($fileName)
  {
    return "$this->baseURI/$this->imageArchivePath/$fileName";
  }

  public function getFileURI ($fileName)
  {
    return "$this->baseURI/$this->fileArchivePath/$fileName";
  }

  public function getImageDownloadURI ($fileId)
  {
    return "$this->frameworkURI/image?id=$fileId";
  }

  public function getFileDownloadURI ($fileId)
  {
    return "$this->frameworkURI/download?id=$fileId";
  }

  /**
   * Given a theme's stylesheet or CSS URI this method returns an unique name
   * suitable for naming a file on the cache folder.
   * @param String $URI The absolute URI of the original file.
   * @return String A file name.
   */
  public function generateCacheFilename ($URI)
  {
    $themesPath = strpos ($URI, $this->themesPath) !== false ? $this->themesPath : $this->defaultThemesPath;
    return str_replace ('/', '_', substr ($URI, strlen ($this->baseURI) + strlen ($themesPath) + 2));
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
    WebConsole::registerPanel ('session', new ConsolePanel ('Session', 'fa fa-user'));
    WebConsole::registerPanel ('database', new ConsolePanel ('Database', 'fa fa-database'));
    WebConsole::registerPanel ('DOM', new ConsolePanel ('DOM', 'fa fa-sitemap'));
    WebConsole::registerPanel ('config', new ConsolePanel ('Config.', 'fa fa-cogs'));
    WebConsole::registerPanel ('exceptions', new ConsolePanel ('Exceptions', 'fa fa-bug'));
    ErrorHandler::$appName = 'Selene Framework';
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

  function initDOMPanel (Controller $controller)
  {
    if (isset($controller->page)) {
      $insp = $controller->page->inspect (true);
      WebConsole::DOM ()->write ($insp);
//      $filter = function ($k, $v) { return $k !== 'parent' && $k !== 'page'; };
//      WebConsole::DOM ()->withFilter($filter, $controller->page);
    }
  }

  private function bootModules ()
  {
    global $application; // Used by the loaded bootstrap.php

    foreach ($this->modules as $path) {
      $boot = "$path/bootstrap.php";
      $f    = @include "$this->modulesPath/$boot";
      if ($f === false)
        $f = @include "$this->defaultModulesPath/$boot";
      if ($f === false)
        throw new ConfigException("File <b>$boot</b> was not found.");
    }
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
    $map = $this->routingMap = new RoutingMap();
    $map->routes = array_merge ($map->routes, $this->routes);
    $map->init ();

    if ($this->debugMode) {
      $filter = function ($k, $v) { return $k !== 'parent' || is_null ($v) ?: '...'; };
      WebConsole::routes()->withFilter ($filter, $this->routingMap->routes);
    }
  }

}
