<?php
namespace Electro\Core\WebApplication;

use Electro\Application;
use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\Core\Assembly\Services\ModulesRegistry;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ProfileInterface;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use PhpKit\WebConsole\DebugConsole\DebugConsoleSettings;
use PhpKit\WebConsole\ErrorConsole\ErrorConsole;

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
   * @var bool
   */
  private $debugMode;
  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * @var WebServer
   */
  private $webServer;

  /**
   * @param InjectorInterface $injector     Provide your favorite dependency injector.
   * @param string            $profileClass The configuration profile's fully qualified class name.
   */
  function __construct (InjectorInterface $injector, $profileClass)
  {
    $this->injector = $injector;
    $injector
      ->share ($injector)
      ->alias (InjectorInterface::class, get_class ($injector))
      ->alias (ProfileInterface::class, $profileClass);
  }

  /**
   * Last resort error handler.
   * <p>It is only activated if an error occurs outside of the HTTP handling pipeline.
   *
   * @param \Exception|\Error $e
   */
  function exceptionHandler ($e)
  {
//    if ($this->logger)
//      $this->logger->error ($e->getMessage (),
//        ['stackTrace' => str_replace ("{$this->app->baseDirectory}/", '', $e->getTraceAsString ())]);
    DebugConsole::outputContent (true);
  }

  /**
   * Bootstraps the application.
   *
   * @param string $rootDir The application's root directory path.
   */
  function run ($rootDir)
  {
    $rootDir = normalizePath ($rootDir);

    /** @var Application $app */
    $app = $this->app = $this->injector
      ->share (Application::class, 'app')
      ->make (Application::class);

    $app->isWebBased = true;
    $app->setup ($rootDir);

    // Pre-assembly setup.

    $this->setupDebugging ($rootDir);
    // Temporarily set framework path mapping here for errors thrown during modules loading.
    ErrorConsole::setPathsMap ($app->getMainPathMap ());

    // Bootstrap the framework/application's modules.

    /** @var Bootstrapper $boot */
    $bootstrapper = $this->injector->make (Bootstrapper::class);
    $bootstrapper->on (Bootstrapper::EVENT_BOOT, function () {
      $this->webServer = $this->injector->make (WebServer::class);
      $this->webServer->setup ();
    });
    $bootstrapper->run ();

    // Post-assembly additional setup.

    if ($this->debugMode)
      $this->setDebugPathsMap ($this->injector->make (ModulesRegistry::class));

    $this->webServer->run ();
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

    $debug = $this->debugMode = getenv ('DEBUG') == 'true';
    $this->injector->defineParam ('debugMode', $debug);

    $debugConsole = getenv ('CONSOLE') == 'true';
    $this->injector->defineParam ('debugConsole', $debugConsole);

    ErrorConsole::init ($debug, $rootDir);
    ErrorConsole::setAppName ($this->app->appName);

    $settings                    = new DebugConsoleSettings;
    $settings->defaultPanelTitle = 'Inspector';
    $settings->defaultPanelIcon  = 'fa fa-search';
    DebugConsole::init ($debug, $settings);
  }

}
