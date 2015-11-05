<?php
namespace Selenia;

use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use PhpKit\WebConsole\ErrorHandler;
use Selenia\Core\Assembly\Config\AssemblyServiceProvider;
use Selenia\Exceptions\Fatal\ConfigException;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Routing\RoutingMap;

class Application
{
  const FRAMEWORK_PATH = 'private/packages/selenia/framework';
  const ref            = __CLASS__;
  /**
   * Holds an array of SEO infomation for each site page or null;
   * @var array
   */
  public $SEOInfo;
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
  /**
   * The real application name.
   * @var string
   */
  public $appName = 'Selenia framework';
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
  public $autoControllerClass = 'Selenia\Http\Controllers\Controller';
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
  /**
   * @var string
   */
  public $cachePath = 'private/storage/cache';
  /**
   * Whether to compress or not the HTTP response with gzip enconding.
   * @var bool
   */
  public $compressOutput = false;
  /**
   * Remove white space around raw markup blocks?
   * @var boolean
   */
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
  public $configFilename = 'application.ini.php';
  /**
   * Folder path for the configuration files.
   * @var string
   */
  public $configPath = 'private/config';
  /**
   * Holds an array of multiple DataSourceInfo for each site page or null;
   * @var array
   */
  public $dataSources;
  /**
   * @var boolean
   */
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
  /**
   * @var boolean
   */
  public $enableCompression;
  /**
   * Favorite icon URL.
   * @var string
   */
  public $favicon = 'data:;base64,iVBORw0KGgo=';
  /**
   * @var string
   */
  public $fileArchivePath = 'private/storage/files';
  /**
   * The path of the framework kernel's directory.
   * @var string
   */
  public $frameworkPath;
  /**
   * @var Boolean True to generate the standard framework scripts.
   */
  public $frameworkScripts = true;
  /**
   * The mapped public URI of the framework's public directory.
   * @var string
   */
  public $frameworkURI = 'framework';
  /**
   * Set to false to disable application-specific sessions and use a global scope.
   * @var Boolean
   */
  public $globalSessions = false;
  /**
   * The homepage's breadcrumb icon class(es).
   * @var string
   */
  public $homeIcon = '';
  /**
   * The homepage's breadcrumb title.
   * @var string
   */
  public $homeTitle = 'Home';
  /**
   * The application's entry point URI.
   *
   * It is also the default URI to redirect to when none is specified on the URL.
   * The URI locates an entry on the routing map where additional info. is used to
   * load the default page.
   * Set by application.ini.php
   * @var String
   */
  public $homeURI = '';
  /**
   * @var string
   */
  public $imageArchivePath = 'private/storage/images';
  /**
   * Set to true to redirect the browser to the generated thumbnail instead of streaming it.
   * @var Boolean
   */
  public $imageRedirection = false;
  /**
   * @var string
   */
  public $imagesCachePath = 'private/storage/cache/images';
  /**
   * The colon delimited list of directory paths.
   * @var string
   */
  public $includePath;
  /**
   * @var \Selenia\Core\DependencyInjection\Injector
   */
  public $injector;
  /**
   * The path of the application's language files' folder, relative to the root folder.
   * @var string
   */
  public $langPath = 'private/resources/lang';
  /**
   * Search paths for module language files, in order of precedence.
   * @var array
   */
  public $languageFolders = [];
  /**
   * List of locale names of languages enabled for this specific application.
   * > Ex: `['en', 'pt']` or `['en-US', 'pt-PT']`
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
   * Relative file path of the view to be used for authenticating the user.
   * <p>It will be searched for on both the active module and on the application.
   * @var string
   */
  public $loginView = '';
  /**
   * @var string
   */
  public $modelPath = 'models';
  /**
   * The relative path of the language files' folder inside a module.
   * @var string
   */
  public $moduleLangPath = 'resources/lang';
  /**
   * The relative path of the public folder inside a module.
   * @var string
   */
  public $modulePublicPath = 'public';
  /**
   * The relative path of the templates folder inside a module.
   * @var string
   */
  public $moduleTemplatesPath = 'resources/templates';
  /**
   * The relative path of the views folder inside a module.
   * @var string
   */
  public $moduleViewsPath = 'resources/views';
  /**
   * A list of modules that are always bootstrapped when the framework boots.
   * <p>A `bootstrap.php` file will be executed on each registered module.
   * @var array
   */
  public $modules = [];
  /**
   * The folder where the framework will search for your application-specific modules.
   * <p>If a module is not found there, it will then search on `defaultModulesPath`.
   * <p>Set by application.ini.php.
   * @var String
   */
  public $modulesPath = 'private/modules';
  /**
   * The application name.
   * This should be composed only of alphanumeric characters. It is used as the session name.
   * @var string
   */
  public $name = 'selenia';
  /**
   * Maximum width and/or height for uploaded images.
   * Images exceeding this dimensions are resized to fit them.
   * @var int
   */
  public $originalImageMaxSize = 1024;
  /**
   * JPEG compression factor for resampled uploaded images.
   * @var int
   */
  public $originalImageQuality = 95;
  /**
   * The URL parameter name used for pagination.
   * @var string
   */
  public $pageNumberParam = 'p';
  /**
   * The default page size for the default data source.
   * @var number
   */
  public $pageSize = 99999;
  /**
   * <p>The fallback folder name where the framework will search for modules.
   * <p>Plugin modules installed as Composer packages will be found there.
   * <p>Set by application.ini.php.
   * @var String
   */
  public $pluginModulesPath = 'private/plugins';
  /**
   * @var string[] A list of "preset" class names.
   */
  public $presets = [];
  /**
   * @var string
   */
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
  /**
   * @var string
   */
  public $storagePath = 'private/storage';
  /**
   * A map of URI prefixes to application configuration files.
   * @var array
   */
  public $subApplications = [];
  /**
   * Registered Matisse tags.
   * @var array
   */
  public $tags = [];
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
  /**
   * Relative to the root folder.
   * @var string
   */
  public $templatesPath = 'private/resources/templates';
  /**
   * A site name that can be used on auto-generated window titles (using the title tag).
   * The symbol @ will be replaced by the current page's title.
   * @var string
   */
  public $title = '@';
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
  public $userModel = '';
  /**
   * Relative to the root folder.
   * @var string
   */
  public $viewPath = 'private/resources/views';
  /**
   * Folders where views can be found.
   * <p>They will be search in order until the requested view is found.
   * @var array
   */
  public $viewsDirectories = [];
  /**
   * The application's main logger.
   * @var Logger
   */
  protected $logger;

  function __construct (InjectorInterface $injector)
  {
    $this->injector = $injector;
  }

  function fromPathToURL ($path)
  {
    return $this->toURL ($this->toURI ($path));
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

  /**
   * Gets an array of file path mappings for the core framework, to aid debugging symlinked directiories.
   * @return array
   */
  function getMainPathMap ()
  {
    $rp = realpath ($this->frameworkPath);
    return $rp != $this->frameworkPath ? [
      $rp => self::FRAMEWORK_PATH,
    ] : [];
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
    $this->directory     = $rootDir;
    $this->baseDirectory = $rootDir;
    $this->rootPath      = $rootDir;
    $this->frameworkPath =
      "$rootDir/" . self::FRAMEWORK_PATH; // due to eventual symlinking, we can't use dirname(__DIR__) here

    $this->setIncludePath ();
//    $this->loadConfiguration ($vuri);

//    $this->templateDirectories[] = $this->toFilePath ($this->templatesPath);
//    $this->viewsDirectories[]    = $this->toFilePath ($this->viewPath);
    $this->languageFolders[] = $this->langPath;
    if (getenv ('APP_DEFAULT_LANG'))
      $this->defaultLang = getenv ('APP_DEFAULT_LANG');

    $assembly = new AssemblyServiceProvider;
    $assembly->register ($this->injector);
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

  protected function loadConfiguration ($vuri)
  {
    $_ = DIRECTORY_SEPARATOR;

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

}
