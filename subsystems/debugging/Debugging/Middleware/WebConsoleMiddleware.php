<?php

namespace Electro\Debugging\Middleware;

use Electro\Debugging\Config\DebugSettings;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Http\Shared\ApplicationRouterInterface;
use Electro\Interfaces\Http\Shared\CurrentRequestInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\Navigation\NavigationLinkInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\Views\ViewInterface;
use Electro\Interfaces\Views\ViewModelInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Routing\Services\RoutingLogger;
use Matisse\Components\Base\Component;
use Matisse\Parser\DocumentContext;
use Matisse\Parser\Expression;
use PhpKit\WebConsole\DebugConsole\DebugConsole;
use PhpKit\WebConsole\Lib\Debug;
use PhpKit\WebConsole\Loggers\Specialized\PSR7RequestLogger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Implements the debugging console for web pages.
 */
class WebConsoleMiddleware implements RequestHandlerInterface
{
  /**
   * @var CurrentRequestInterface
   */
  private $currentRequest;
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

  function __construct (KernelSettings $kernelSettings, InjectorInterface $injector, DebugSettings $debugSettings,
                        CurrentRequestInterface $currentRequest)
  {
    $this->kernelSettings = $kernelSettings;
    $this->injector       = $injector;
    $this->debugSettings  = $debugSettings;
    $this->currentRequest = $currentRequest;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    if (!$this->debugSettings->webConsole || $request->hasHeader ('X-Requested-With'))
      return $next ();

    //------------------------------------------------------------------

    /** @var ViewServiceInterface $viewService */
    $viewService = null;

    //------------------
    // Logging panel
    //------------------
    if (extension_loaded ('xdebug'))
      DebugConsole::defaultLogger ()
                  ->write ('<#alert><b>Warning:</b> When running with Xdebug enabled, the framework\'s performance is severely degraded, especially on debug mode.</#alert>'
                           . '<p class=__comment>Refer to the framework\'s documentation for more information.</p>');

    //------------------
    // Config. panel
    //------------------
    if ($this->debugSettings->logConfig)
      DebugConsole::logger ('config')->inspect ($this->kernelSettings);

    //------------
    // View panel
    //------------
    if ($this->debugSettings->logView) {

      $viewService = $this->injector->make (ViewServiceInterface::class);

//      $VMFilter = function ($k, $v, $o) {
//        if (
//          $v instanceof DocumentContext ||
//          $v instanceof Component ||
//          $k === 'parent' ||
//          $k === 'model'
//        ) return '...';
//        return true;
//      };

      DebugConsole::logger ('view')
                  ->write ('<#section|View Service>')
                  ->inspect ($viewService)
                  ->write ('</#section>');
    }

    //------------
    // Model panel
    //------------
    if ($this->debugSettings->logModel) {

      if (!$viewService)
        $viewService = $this->injector->make (ViewServiceInterface::class);

      $VMFilter = function ($k, $v, $o) {
        if ($v instanceof KernelSettings ||
            $v instanceof NavigationInterface ||
            $v instanceof NavigationLinkInterface ||
            $v instanceof SessionInterface ||
            $v instanceof ServerRequestInterface ||
            $v instanceof DocumentContext ||
            $v instanceof Component
        ) return '...';
        return true;
      };

      $renderCount = 0;
      $viewService->onRenderView (function (ViewModelInterface $viewModel, ViewInterface $view) use (
        $VMFilter, &$renderCount
      ) {
        ++$renderCount;
        $path = $view->getPath ();
        $log  = DebugConsole::logger ('model');
        $log->write (sprintf ("<#section|$renderCount. %s>",
          $path ? pathinfo ($path, PATHINFO_FILENAME) : 'Unnamed view'))
            ->write ('<#header>View</#header>')
            ->write (Debug::rawGrid ([
              'Template'         => $path ?: '<i>dynamic template</i>',
              'Rendering engine' => Debug::typeInfoOf ($view->getEngine ()),
              'View model class' => isset($viewModel) ? Debug::typeInfoOf ($viewModel) : '<i>none</i>',
            ]));
        if ($viewModel)
          $log->write ('<#header>View model data</#header>')
              ->inspectWithNoTypeInfo ($viewModel, $VMFilter);
        $log->write ("</#section>");
      });
    }

    //------------------------------------------------------------------------------------------------------------------
    // CHAIN TO NEXT MIDDLEWARE
    //------------------------------------------------------------------------------------------------------------------

    /** @var ResponseInterface $response */
    $response = $next ();

    // Note: any exceptions thrown from the next middlewares on the stack should be handled by ErrorHandlingMiddlware,
    // so execution should be able to proceed.

    //------------------------------------------------------------------------------------------------------------------
    // AFTER REQUEST WAS HANDLED
    //------------------------------------------------------------------------------------------------------------------

    //------------------
    // Request panel
    //------------------
    if ($this->debugSettings->logRequest) {
      /** @var PSR7RequestLogger $log */
      $log = DebugConsole::logger ('request');
      if (!$log->hasRequest ())
        $log->setRequest ($this->currentRequest->getInstance ());
    }

    //------------------
    // Response panel
    //------------------
    if ($this->debugSettings->logResponse)
      DebugConsole::logger ('response')->setResponse ($response);

    //------------------
    // Session panel
    //------------------
    if ($this->debugSettings->logSession && $this->injector->provides (SessionInterface::class)) {
      /** @var SessionInterface $session */
      $session = $this->injector->make (SessionInterface::class);
      $logger  = DebugConsole::logger ('session');

      // Display a button to force a Log Out.
      if ($session->loggedIn ()) {
        $url   = $request->getUri ();
        $query = $url->getQuery ();
        $query = ($query === '' ? '' : '&') . AlternateLogoutMiddleware::LOGOUT_PARAM;
        $url   = $url->withQuery ($query);
        $logger->write ("<button type=\"button\" class=\"__btn __btn-default\" style=\"position:absolute;right:5px;top:5px\" onclick=\"location.href='$url'\">Log out</button>");
      }

      $logger->inspect ($session);
    }

    //------------------
    // Routing panel
    //------------------
    if ($this->debugSettings->logRouting) {
      $router = $this->injector->make (ApplicationRouterInterface::class);

      $handlers = $router->__debugInfo ()['handlers'];

      $rootR = $handlers
        ? implode ('', map ($handlers, function ($r) {
          return sprintf ('<#row><span class="__type">%s</span></#row>', is_string ($r) ? $r : typeOf ($r));
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
                  ->write ("<#row>Exit middleware stack</#row>")
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

    //----------------------------------------------------------------------------------------
    // View panel (again)
    //----------------------------------------------------------------------------------------
    if ($this->debugSettings->logView) {
      $expMap = Expression::$translationCache;
      ksort ($expMap);
      DebugConsole::logger ('view')
                  ->write ('<#section|Compiled expressions>')
                  ->inspect ($expMap)
                  ->write ('</#section>');
    }

    //------------------------------------------------------------------
    // GENERATE FINAL RESPONSE
    //------------------------------------------------------------------

    $contentType = $response->getHeaderLine ('Content-Type');
    $status      = $response->getStatusCode ();
    if ($status >= 300 && $status < 400 || $contentType && $contentType != 'text/html')
      return $response;

    $response->getBody ()->rewind ();

    return DebugConsole::outputContentViaResponse ($request, $response, true);
  }

}
