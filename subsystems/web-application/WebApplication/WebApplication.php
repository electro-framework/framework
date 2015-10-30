<?php
namespace Selenia\WebApplication;
use PhpKit\WebConsole\ErrorHandler;
use PhpKit\WebConsole\WebConsole;
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
    WebConsole::outputContent (true);
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
      ->share (Application::ref)
      ->make (Application::ref);
    $application->setup ($rootDir);

    // Pre-assembly setup.

    $this->setupDebugging ($rootDir);
    // Temporarily set framework path mapping here for errors thrown during modules loading.
    ErrorHandler::setPathsMap ($application->getMainPathMap ());

    // Bootstrap the application's modules.

    /** @var ModulesManager $modulesApi */
    $modulesManager = $this->injector->make (ModulesManager::ref);
    $modulesManager->bootModules ();

    // Post-assembly additional setup.

    if ($application->debugMode)
      $this->setDebugPathsMap ($this->injector->make (ModulesRegistry::ref));

    /** @var WebServer $webServer */
    $webServer = $this->injector->make (WebServer::ref);
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
    ErrorHandler::setPathsMap ($map);
  }

  /**
   * @param string $rootDir
   */
  private function setupDebugging ($rootDir)
  {
    set_exception_handler ([$this, 'exceptionHandler']);
    $debug = $this->app->debugMode = getenv ('APP_DEBUG') == 'true';

    ErrorHandler::init ($debug, $rootDir);
    ErrorHandler::$appName = $this->app->appName;
    WebConsole::init ($debug);
  }

}
