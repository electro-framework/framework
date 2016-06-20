<?php
namespace Electro\Debugging\Middleware;

use PhpKit\WebConsole\DebugConsole\DebugConsole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Electro\Application;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Http\Shared\ApplicationRouterInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Routing\Services\RoutingLogger;

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
   * @var bool
   */
  private $debugConsole;
  /**
   * @var InjectorInterface
   */
  private $injector;

  /**
   * WebConsoleMiddleware constructor.
   *
   * @param Application       $app
   * @param InjectorInterface $injector
   * @param bool              $debugConsole
   */
  function __construct (Application $app, InjectorInterface $injector, $debugConsole)
  {
    $this->app          = $app;
    $this->injector     = $injector;
    $this->debugConsole = $debugConsole;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    if (!$this->debugConsole) // In case the middlware was registered.
      return $next ();
    //------------------------------------------------------------------

    /** @var ResponseInterface $response */
    $response = $next ();

    $contentType = $response->getHeaderLine ('Content-Type');
    if ($contentType && $contentType != 'text/html')
      return $response;

    $response->getBody ()->rewind ();

    //------------------
    // Logging panel
    //------------------
    if (extension_loaded ('xdebug'))
      DebugConsole::defaultLogger ()
                  ->write ('<#alert><b>Warning:</b> When running with Xdebug enabled, the framework\'s performance is severely degraded, especially on debug mode.</#alert>'
                           . '<p class=__comment>Refer to the framework\'s documentation for more information.</p>');

    //------------------
    // Request panel
    //------------------
    $log = DebugConsole::logger ('request');
    if (!$log->hasRequest ())
      $log->setRequest ($request);

    //------------------
    // Response panel
    //------------------
    DebugConsole::logger ('response')->setResponse ($response);

    //------------------
    // Routing panel
    //------------------
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

    //------------------
    // Navigation panel
    //------------------
    if ($this->injector->provides (NavigationInterface::class)) {
      try {
        /** @var NavigationInterface $navigation */
        $navigation = $this->injector->make (NavigationInterface::class);
      // Do not log the navigation if its middleware was not executed.
      if ($navigation->request ())
        DebugConsole::logger ('navigation')->withFilter (function ($k, $v, $o) use ($navigation) {
          if ($k === 'parent' || $k === 'request') return '...';
          if ($k === 'IDs' && $o != $navigation->rootLink ()) return '...';
          return true;
        }, $navigation);
      }
      catch (\Exception $e) {}
    }

    //------------------
    // Config. panel
    //------------------
    DebugConsole::logger ('config')->inspect ($this->app);

    //------------------
    // Session panel
    //------------------
    if ($this->injector->provides (SessionInterface::class)) {
      DebugConsole::logger ('session')
                  ->write ('<button type="button" class="__btn __btn-default" style="position:absolute;right:5px;top:5px" onclick="location.href=\'logout\'">Log out</button>')
                  ->inspect ($this->injector->make (SessionInterface::class));
    }

    //------------------
    // Tracing panel
    //------------------
    $trace = DebugConsole::logger ('trace');
    if ($trace->hasContent ())
      DebugConsole::registerPanel ('trace', $trace);


    return DebugConsole::outputContentViaResponse ($request, $response, true);
  }

}
