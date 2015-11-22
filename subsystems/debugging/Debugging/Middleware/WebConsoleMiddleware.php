<?php
namespace Selenia\Debugging\Middleware;
use Monolog\Logger;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use PhpKit\WebConsole\Loggers\ConsoleLogger;
use PhpKit\WebConsole\Loggers\Handlers\WebConsoleMonologHandler;
use PhpKit\WebConsole\Loggers\Specialized\PSR7RequestLogger;
use PhpKit\WebConsole\Loggers\Specialized\PSR7ResponseLogger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Selenia\Application;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\SessionInterface;
use Selenia\Routing\Router;

/**
 *
 */
class WebConsoleMiddleware implements RequestHandlerInterface
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
   * @var LoggerInterface
   */
  private $logger;
  /**
   * @var SessionInterface
   */
  private $session;

  function __construct (Application $app, InjectorInterface $injector, SessionInterface $session,
                        LoggerInterface $logger)
  {
    $this->app      = $app;
    $this->injector = $injector;
    $this->session  = $session;
    $this->logger   = $logger;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $app = $this->app;
    DebugConsole::registerLogger ('request', new PSR7RequestLogger ('Request', 'fa fa-paper-plane'));
    DebugConsole::registerLogger ('response', new PSR7ResponseLogger ('Response', 'fa fa-file'));
    DebugConsole::registerLogger ('routes', new ConsoleLogger ('Routes', 'fa fa-location-arrow'));
    DebugConsole::registerLogger ('config', new ConsoleLogger ('Config.', 'fa fa-cogs'));
    DebugConsole::registerLogger ('session', new ConsoleLogger ('Session', 'fa fa-user'));
    DebugConsole::registerLogger ('DOM', new ConsoleLogger ('DOM', 'fa fa-sitemap'));
    DebugConsole::registerLogger ('vm', new ConsoleLogger ('View Models', 'fa fa-table'));
    DebugConsole::registerLogger ('database', new ConsoleLogger ('Database', 'fa fa-database'));
//    WebConsole::registerPanel ('exceptions', new ConsolePanel ('Exceptions', 'fa fa-bug'));

    // Redirect logger to Inspector panel
    if (isset($this->logger))
      if ($this->logger instanceof Logger)
        $this->logger->pushHandler (new WebConsoleMonologHandler(getenv('DEBUG_LEVEL') || Logger::DEBUG));

    /** @var ResponseInterface $response */
    $response = $next ();

    $contentType = $response->getHeaderLine ('Content-Type');
    if ($contentType && $contentType != 'text/html')
      return $response;

    $response->getBody ()->rewind ();

    // Request panel
    DebugConsole::logger ('request')->setRequest ($request);

    // Response panel
    DebugConsole::logger ('response')->setResponse ($response);

    // Config. panel
    DebugConsole::logger ('config')->inspect ($app);

    // Session panel
    DebugConsole::logger ('session')
                ->write ('<button type="button" class="__btn __btn-default" style="position:absolute;right:5px;top:5px" onclick="__doAction(\'logout\')">Log out</button>')
                ->inspect ($this->session);

    // Routes panel
    /** @var Router $router */
    $router = $this->injector->make ('Selenia\Routing\Router');
    if (isset($router->controller)) {

      // DOM panel
      if (isset($router->controller->page)) {
        $insp = $router->controller->page->inspect (true);
        DebugConsole::logger ('DOM')->write ($insp);
      }
//      $filter = function ($k, $v) { return $k !== 'parent' && $k !== 'page'; };
//      WebConsole::DOM ()->withFilter($filter, $controller->page);

      // View Models panel
      DebugConsole::logger ('vm')->inspect (get_object_vars ($router->controller));
    }

    return DebugConsole::outputContentViaResponse ($request, $response, true);
  }
}
