<?php
namespace Electro\Debugging\Middleware;

use Electro\Debugging\Config\DebugSettings;
use Electro\Interfaces\DI\InjectorInterface;
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
   * @var bool
   */
  private $debugSettings;
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
   * @param DebugSettings     $debugSettings
   */
  function __construct (KernelSettings $kernelSettings, InjectorInterface $injector, DebugSettings $debugSettings)
  {
    $this->kernelSettings = $kernelSettings;
    $this->injector       = $injector;
    $this->debugSettings  = $debugSettings;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    if (!$this->debugSettings->webConsole)
      return $next ();
    //------------------------------------------------------------------

    /** @var ResponseInterface $response */
    $response = $next ();

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
    if ($this->debugSettings->logRequest) {
      $log = DebugConsole::logger ('request');
      if (!$log->hasRequest ())
        $log->setRequest ($request);
    }

    //------------------
    // Response panel
    //------------------
    if ($this->debugSettings->logResponse)
      DebugConsole::logger ('response')->setResponse ($response);

    //------------------
    // Routing panel
    //------------------
    if ($this->debugSettings->logRouting) {
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
    }

    //------------------
    // Navigation panel
    //------------------
    if ($this->debugSettings->logNavigation) {
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
    }

    //------------------
    // Config. panel
    //------------------
    if ($this->debugSettings->logConfig)
      DebugConsole::logger ('config')->inspect ($this->kernelSettings);

    //------------------
    // Session panel
    //------------------
    if ($this->debugSettings->logSession && $this->injector->provides (SessionInterface::class)) {
      DebugConsole::logger ('session')
                  ->write ('<button type="button" class="__btn __btn-default" style="position:absolute;right:5px;top:5px" onclick="location.href=\'logout\'">Log out</button>')
                  ->inspect ($this->injector->make (SessionInterface::class));
    }

    return DebugConsole::outputContentViaResponse ($request, $response, true);
  }

}
