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
use Selenia\Interfaces\Http\Shared\ApplicationRouterInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\SessionInterface;
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
    DebugConsole::registerPanel ('config', new ConsoleLogger ('Configuration', 'fa fa-cogs'));
    DebugConsole::registerPanel ('session', new ConsoleLogger ('Session', 'fa fa-user'));
    DebugConsole::registerPanel ('DOM', new ConsoleLogger ('Server-side DOM', 'fa fa-sitemap'));
    DebugConsole::registerPanel ('vm', new ConsoleLogger ('View Model', 'fa fa-table'));
    DebugConsole::registerPanel ('database', new ConsoleLogger ('Database', 'fa fa-database'));
//    DebugConsole::registerPanel ('exceptions', new ConsoleLogger ('Exceptions', 'fa fa-bug'));
    $trace = DebugConsole::registerLogger ('trace', new ConsoleLogger ('Trace', 'fa fa-clock-o big'));

    // Redirect logger to Inspector panel
    if (isset($this->logger))
      if ($this->logger instanceof Logger)
        $this->logger->pushHandler (new WebConsoleMonologHandler(getenv ('DEBUG_LEVEL') || Logger::DEBUG));

    //------------------------------------------------------------------
    /** @var ResponseInterface $response */
    $response = $next ();
    //------------------------------------------------------------------

    $contentType = $response->getHeaderLine ('Content-Type');
    if ($contentType && $contentType != 'text/html')
      return $response;

    $response->getBody ()->rewind ();

    // Logging panel
    if (extension_loaded ('xdebug'))
      DebugConsole::defaultLogger ()
                  ->write ('<#alert><b>Warning:</b> When running with Xdebug enabled, the framework\'s performance is severely degraded, especially on debug mode.</#alert>'
                           . '<p class=__comment>Refer to the framework\'s documentation for more information.</div>');

    // Request panel
    DebugConsole::logger ('request')->setRequest ($request);

    // Response panel
    DebugConsole::logger ('response')->setResponse ($response);

    // Navigation panel
    if ($this->injector->provides (NavigationInterface::class)) {
      /** @var NavigationInterface $navigation */
      $navigation = $this->injector->make (NavigationInterface::class);
      // Do not log the navigation if its middleware was not executed.
      if ($navigation->request ())
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

    $router = $this->injector->make (ApplicationRouterInterface::class);

    $handlers = $router->__debugInfo ()['handlers'];

    $rootR = $handlers
      ? implode ('', map ($handlers, function ($r) {
        return sprintf ('<#row><#type>%s</#type></#row>', is_string ($r) ? $r : typeOf ($r));
      }))
      : '<#i><i>empty</i></#i>';

    $logger = $this->injector->make (RoutingLogger::class);
    $log    = $logger->getContent ();

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

    if ($trace->hasContent ())
      DebugConsole::registerPanel ('trace', $trace);


    return DebugConsole::outputContentViaResponse ($request, $response, true);
  }

}
