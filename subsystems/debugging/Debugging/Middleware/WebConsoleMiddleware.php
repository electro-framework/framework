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
use Selenia\Interfaces\Http\Shared\ApplicationMiddlewareInterface;
use Selenia\Interfaces\Http\Shared\ApplicationRouterInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\SessionInterface;
use Selenia\Routing\Services\Router;
use Selenia\Routing\Services\RoutingLogger;

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

  function __construct (Application $app, InjectorInterface $injector, LoggerInterface $logger)
  {
    $this->app      = $app;
    $this->injector = $injector;
    $this->logger   = $logger;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $app = $this->app;
    DebugConsole::registerPanel ('request', new PSR7RequestLogger ('Request', 'fa fa-paper-plane'));
    DebugConsole::registerPanel ('response', new PSR7ResponseLogger ('Response', 'fa fa-file'));
    DebugConsole::registerPanel ('routes', new ConsoleLogger ('Routing', 'fa fa-location-arrow'));
    DebugConsole::registerPanel ('navigation', new ConsoleLogger ('Navigation', 'fa fa-compass big'));
    DebugConsole::registerPanel ('config', new ConsoleLogger ('Config.', 'fa fa-cogs'));
    DebugConsole::registerPanel ('session', new ConsoleLogger ('Session', 'fa fa-user'));
//    DebugConsole::registerPanel ('DOM', new ConsoleLogger ('DOM', 'fa fa-sitemap'));
//    DebugConsole::registerPanel ('vm', new ConsoleLogger ('View Models', 'fa fa-table'));
    DebugConsole::registerPanel ('database', new ConsoleLogger ('Database', 'fa fa-database'));
//    WebConsole::registerPanel ('exceptions', new ConsolePanel ('Exceptions', 'fa fa-bug'));

    // Redirect logger to Inspector panel
    if (isset($this->logger))
      if ($this->logger instanceof Logger)
        $this->logger->pushHandler (new WebConsoleMonologHandler(getenv ('DEBUG_LEVEL') || Logger::DEBUG));

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

    // Navigation panel
    if ($this->injector->provides (NavigationInterface::class)) {
      $navigation = $this->injector->make (NavigationInterface::class);
      DebugConsole::logger ('navigation')->inspect ($navigation);
    }

    // Config. panel
    DebugConsole::logger ('config')->inspect ($app);

    // Session panel
    if ($this->injector->provides (SessionInterface::class)) {
      DebugConsole::logger ('session')
                  ->write ('<button type="button" class="__btn __btn-default" style="position:absolute;right:5px;top:5px" onclick="__doAction(\'logout\')">Log out</button>')
                  ->inspect ($this->injector->make (SessionInterface::class));
    }

    // Routes panel
    /** @var Router $router */
//    $router = $this->injector->make ('Selenia\Routing\Router');
//    if (isset($router->controller)) {
//
//      // DOM panel
//      if (isset($router->controller->page)) {
//        $insp = $router->controller->page->inspect (true);
//        DebugConsole::logger ('DOM')->write ($insp);
//      }
//      $filter = function ($k, $v) { return $k !== 'parent' && $k !== 'page'; };
//      WebConsole::DOM ()->withFilter($filter, $controller->page);
//
//      // View Models panel
//      DebugConsole::logger ('vm')->inspect (get_object_vars ($router->controller));
//    }

    /** @var ApplicationMiddlewareInterface $middlewareStack */
//    $middlewareStack = $this->injector->make (ApplicationMiddlewareInterface::class);
    $router = $this->injector->make (ApplicationRouterInterface::class);

    $handlers = $router->__debugInfo ()['handlers'];

    $rootR = $handlers
      ? implode ('', map ($handlers, function ($r) {
        return sprintf ('<#row><#type>%s</#type></#row>', is_string ($r) ? $r : typeOf ($r));
      }))
      : '<#i><i>empty</i></#i>';

    $logger = $this->injector->make (RoutingLogger::class);
    $log    = $logger->getContent ();
//    echo $log;exit;

//    DebugConsole::logger ('routes')
//                ->write ("<#section|REGISTERED ROUTERS>$rootR</#section>");
    DebugConsole::logger ('routes')
                ->write ("<#section|REGISTERED ROUTERS>$rootR</#section>" .
                         "<#section|APPLICATION MIDDLEWARE STACK &nbsp;┊&nbsp; RUN HISTORY>")
                ->write ($log)
                ->write ("<#row>Return from ")->typeName ($this)->write ("</#row>")
                ->write ("<#row><i>(log entries from this point on can't be displayed)</i></#row>")
                ->write ("</#indent>")
                ->write ("<#row>Exit stack 1</#row>")
                ->write ("<#row>End of routing log</#row>")
                ->write ("</#section>");

    return DebugConsole::outputContentViaResponse ($request, $response, true);
  }

}
