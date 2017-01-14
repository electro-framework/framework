<?php
namespace Electro\Debugging\Middleware;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Http\Shared\ApplicationRouterInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Routing\Services\RoutingLogger;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 *
 */
class WebConsoleMiddleware implements RequestHandlerInterface
{
  /**
   * Request parameter to be added to the current URL for forcing a log out.
   * The URL path is preserved so that we may clear the correct cookie for the current URL.
   * This is triggered from a web console button.
   */
  const LOGOUT_PARAM = 'debug-logout';
  /**
   * @var bool
   */
  private $webConsole;
  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * @var KernelSettings
   */
  private $kernelSettings;

  /**
   * WebConsoleMiddleware constructor.
   *
   * @param KernelSettings    $kernelSettings
   * @param InjectorInterface $injector
   * @param bool              $webConsole
   */
  function __construct (KernelSettings $kernelSettings, InjectorInterface $injector, $webConsole)
  {
    $this->kernelSettings = $kernelSettings;
    $this->injector       = $injector;
    $this->webConsole   = $webConsole;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    if (!$this->webConsole) // In case the middleware was registered.
      return $next ();
    //------------------------------------------------------------------

    /** @var ResponseInterface $response */
    $response = $next ();
    $uri      = $request->getUri ();

    // Allow logging out via web console (this must run after the session middleware)

    if (str_endsWith ($uri->getQuery (), self::LOGOUT_PARAM)) {
      /** @var SessionInterface $session */
      $session = $this->injector->make (SessionInterface::class);
      /** @var RedirectionInterface $redirection */
      $redirection = $this->injector->make (RedirectionInterface::class);
      $redirection->setRequest ($request);

      $session->logout ();
      $query = substr ($uri->getQuery (), 0, -strlen (self::LOGOUT_PARAM));
      return $redirection->to ($uri->withQuery ($query));
    }

    $contentType = $response->getHeaderLine ('Content-Type');
    $status      = $response->getStatusCode ();
    if ($status >= 300 && $status < 400 || $contentType && $contentType != 'text/html')
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
        DebugConsole::logger ('navigation')->withFilter (function ($k, $v, $o) use ($navigation) {
          if ($k === 'parent' || $k === 'request') return '...';
          if ($k === 'IDs' && $o != $navigation->rootLink ()) return '...';
          return true;
        }, $navigation);
      }
      catch (\Exception $e) {
      }
    }

    //------------------
    // Config. panel
    //------------------
    DebugConsole::logger ('config')->inspect ($this->kernelSettings);

    //------------------
    // Session panel
    //------------------
    if ($this->injector->provides (SessionInterface::class)) {
      /** @var SessionInterface $session */
      $session = $this->injector->make (SessionInterface::class);
      $logger  = DebugConsole::logger ('session');
      if ($session->loggedIn ()) {
        $query = $uri->getQuery ();
        $query = $query === '' ? self::LOGOUT_PARAM : '&' . self::LOGOUT_PARAM;
        $url   = $uri->withQuery ($query);
        $logger->write ("<button type=\"button\" class=\"__btn __btn-default\" style=\"position:absolute;right:5px;top:5px\" onclick=\"location.href='$url'\">Log out</button>");
      }
      $logger->inspect ($session);
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
