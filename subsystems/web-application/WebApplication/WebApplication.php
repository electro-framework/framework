<?php
namespace Selenia\WebApplication;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use PhpKit\WebConsole\DebugConsole\DebugConsoleSettings;
use PhpKit\WebConsole\ErrorConsole\ErrorConsole;
use Selenia\Application;
use Selenia\Core\Assembly\Services\ModulesManager;
use Selenia\Core\Assembly\Services\ModulesRegistry;
use Selenia\Interfaces\InjectorInterface;
use Selenia\WebServer\WebServer;
use Zend\Diactoros\Response;

/**
 * Provides the standard bootstrap procedure for web applications.
 *
 * - Sets up all framework services required for HTTP request handling.
 * - Transfers execution to the web-server subsystem.
 */
class WebApplication
{
  /**
   * @var Application
   */
  private $app;
  /**
   * @var InjectorInterface
   */
  private $injector;

  /**
   * @param InjectorInterface $injector Provide your favorite dependency injector.
   */
  function __construct (InjectorInterface $injector)
  {
    $this->injector = $injector;
    $injector
      ->share ($injector)
      ->alias ('Selenia\Interfaces\InjectorInterface', get_class ($injector));
  }

  /**
   * Last resort error handler.
   * <p>It is only activated if an error occurs outside of the HTTP handling pipeline.
   * @param \Exception|\Error $e
   */
  function exceptionHandler ($e)
  {
    if (function_exists ('database_rollback'))
      database_rollback ();
//    if ($this->logger)
//      $this->logger->error ($e->getMessage (),
//        ['stackTrace' => str_replace ("{$this->app->baseDirectory}/", '', $e->getTraceAsString ())]);
    DebugConsole::outputContent (true);
  }

  /**
   * Bootstraps the application.
   * @param string $rootDir The application's root directory path.
   */
  function run ($rootDir)
  {
    global $application; //TODO: remove this when feasible

    // Create and register the foundational framework services.

    $application = $this->app = $this->injector
      ->share (Application::class)
      ->make (Application::class);
    $application->setup ($rootDir);

    // Pre-assembly setup.

    $this->setupDebugging ($rootDir);
    // Temporarily set framework path mapping here for errors thrown during modules loading.
    ErrorConsole::setPathsMap ($application->getMainPathMap ());

    // Bootstrap the application's modules.

    /** @var ModulesManager $modulesApi */
    $modulesManager = $this->injector->make (ModulesManager::class);
    $modulesManager->bootModules ();

    // Post-assembly additional setup.

    if ($application->debugMode)
      $this->setDebugPathsMap ($this->injector->make (ModulesRegistry::class));

    /** @var WebServer $webServer */
    $webServer = $this->injector->make (WebServer::class);
    $webServer->setup ();
    $webServer->run ();
  }

  /**
   * Configures path mappings for the ErrorHandler, so that links to files on symlinked directories are converted to
   * links on the main project tree, allowing easier files editing on an IDE.
   *
   * @param ModulesRegistry $registry
   */
  private function setDebugPathsMap (ModulesRegistry $registry)
  {
    $map = $this->app->getMainPathMap ();
    $map = array_merge ($map, $registry->getPathMappings ());
    ErrorConsole::setPathsMap ($map);
  }

  /**
   * @param string $rootDir
   */
  private function setupDebugging ($rootDir)
  {
    set_exception_handler ([$this, 'exceptionHandler']);
    $debug = $this->app->debugMode = getenv ('APP_DEBUG') == 'true';

    ErrorConsole::init ($debug, $rootDir);
    ErrorConsole::setAppName ($this->app->appName);

    $settings = new DebugConsoleSettings;
    $settings->defaultPanelTitle = 'Inspector';
    $settings->defaultPanelIcon = 'fa fa-search';
    DebugConsole::init ($debug, $settings);
  }

}
