<?php
namespace Selenia\WebServer;
use PhpKit\WebConsole\ErrorHandler;
use PhpKit\WebConsole\WebConsole;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Core\Assembly\Services\ModulesRegistry;
use Selenia\FileServer\Services\FileServerMappings;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\MiddlewareStackInterface;
use Selenia\Interfaces\ResponseSenderInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Responds to an HTTP request made to the web application by loading and running all the framework subsystems required
 * to handle that request.
 */
class WebServer
{
  /**
   * @var Application
   */
  private $app;
  /**
   * @var FileServerMappings
   */
  private $fileServerMappings;
  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * @var MiddlewareStackInterface
   */
  private $middlewareStack;
  /**
   * @var ResponseSenderInterface
   */
  private $responseSender;

  /**
   * @param InjectorInterface        $injector Provide your favorite dependency injector.
   * @param MiddlewareStackInterface $middlewareStack
   * @param ResponseSenderInterface  $responseSender
   * @param FileServerMappings       $fileServerMappings
   */
  function __construct (InjectorInterface $injector, MiddlewareStackInterface $middlewareStack,
                        ResponseSenderInterface $responseSender, FileServerMappings $fileServerMappings)
  {
    $this->injector = $injector;
    $injector
      ->share ($injector)
      ->alias ('Selenia\Interfaces\InjectorInterface', get_class ($injector));
    $this->fileServerMappings = $fileServerMappings;
    $this->middlewareStack    = $middlewareStack;
    $this->responseSender     = $responseSender;
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
   * Runs the web server's request handler.
   * @param string $rootDir The application's root directory path.
   */
  function run ($rootDir)
  {
    global $application; //TODO: remove this when feasible

    $application = $this->app = $this->injector->make (Application::ref);
    $this->setupDebugging ($rootDir);
    $application->setup ($rootDir);

    // Temporarily set framework path mapping here for errors thrown during modules loading.
    ErrorHandler::setPathsMap ($application->getMainPathMap ());

    // Load bootable modules.
    $application->boot ();

    // Post-boot additional setup.

    if ($application->debugMode)
      $this->setDebugPathsMap ($this->injector->make (ModulesRegistry::ref));

    $this->fileServerMappings->map ($application->frameworkURI,
      $application->frameworkPath . DIRECTORY_SEPARATOR . $application->modulePublicPath);

    // Process the request.

    $request    = ServerRequestFactory::fromGlobals ();
    $application->baseURI = $this->getBaseUri ($request);
    $application->VURI = $this->getVirtualUri ($request);
    $request    = $request->withAttribute ('baseUri', $application->baseURI);
    $request    = $request->withAttribute ('virtualUri', $application->VURI);
    $response   = new Response;
    $middleware = $this->middlewareStack;
    $response   = $middleware ($request, $response, null);
    if (!$response) return;

    // Send back the response.

    $this->responseSender->send ($response);
  }

  private function getVirtualUri (ServerRequestInterface $request)
  {
    $params  = $request->getServerParams ();
    $uri     = get ($params, 'REQUEST_URI');
    $baseURI = $request->getAttribute('baseUri');
    $vuri    = substr ($uri, strlen ($baseURI) + 1) ?: '';
    if (($p = strpos ($vuri, '?')) !== false)
      $vuri = substr ($vuri, 0, $p);
    return $vuri;
  }

  private function getBaseUri (ServerRequestInterface $request) {
    $params  = $request->getServerParams ();
    return dirnameEx (get ($params, 'SCRIPT_NAME'));
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
