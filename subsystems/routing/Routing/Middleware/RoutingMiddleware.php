<?php
namespace Electro\Routing\Middleware;

use Electro\Debugging\Config\DebugSettings;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\RouteMatcherInterface;
use Electro\Interfaces\Http\Shared\ApplicationRouterInterface;
use Electro\Interfaces\Http\Shared\CurrentRequestInterface;
use Electro\Routing\Lib\BaseRouter;
use Electro\Routing\Lib\Debug\RouterLoggingTrait;
use Electro\Routing\Services\MiddlewareStack;
use Electro\Routing\Services\RoutingLogger;
use PhpKit\WebConsole\Lib\Debug;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Performs the application's HTTP request routing.
 *
 * <p>This is a middleware that holds and iterates a list of routers.
 *
 * Each router may return:
 * 1. a {@see ResponseInterface} object - this middleware will stop the iteration and return the response to the
 * previous middleware.
 * 2. `null` - this middleware will iterate to the next router. If there is none, it will call the next middleware (via
 * `$next`).
 *
 * <p>{@see ApplicationRouterInterface} is usually an injection alias of this class.
 *
 * <br>
 * > **Note:** unlike {@see BaseRouter} this class provides its own route trace logging; it's not dependent on a
 * subclass like {@see BaseRouterWithLogging}.
 */
class RoutingMiddleware extends MiddlewareStack
  implements ApplicationRouterInterface /* for call-signature compatibility */

{
  use RouterLoggingTrait;

  /**
   * @var DebugSettings
   */
  private $debugSettings;
  /**
   * @var RoutingLogger
   */
  private $routingLogger;

  public function __construct (RouteMatcherInterface $matcher, InjectorInterface $injector,
                               CurrentRequestInterface $currentRequest, DebugSettings $debugSettings,
                               RoutingLogger $routingLogger)
  {
    parent::__construct ($matcher, $injector, $currentRequest);
    $this->debugSettings = $debugSettings;
    $this->routingLogger = $routingLogger;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    // Note: the following change to the Request is router-specific and it's not required by other middleware.
    // Only the router uses a mutating requestTarget to handle sub-routes.
    // TODO: route using UriInterface and do not change requestTarget.

    /** @var ServerRequestInterface $request */
    $request = $request->withRequestTarget (either ($request->getAttribute ('virtualUri'), '.'));

    //----------

    // Note: the `next` argument is never used by routers, so we'll just pass a no-op callback, just to match the
    // expected middleware interface.
    $r = parent::__invoke ($request, $response, back ());
    // If a router found a route, return the generated response.
    if ($r) return $r;

    // If not match was found on any router (the final response is `null`), call the next middleware.
    if ($this->debugSettings->webConsole)
      $this->routingLogger->write ("<#row|__alert>The main router found <b>no route matching the current URL</b></#row>");
    return $next ();
  }

  protected function iteration_stepMatchMiddleware ($key, $routable, ServerRequestInterface $request,
                                                    ResponseInterface $response, callable $next)
  {
    if ($this->debugSettings->webConsole) {
      if (is_string ($routable)) {
        $c = $routable;
        $c = str_extract ($c, '#\\\\(\w+)\\\\Config#');
        $c = "of module <kbd>$c</kbd>";
      }
      elseif (is_array ($routable))
        return parent::iteration_stepMatchMiddleware ($key, $routable, $request, $response, $next);
      else $c = "of type " . Debug::getType ($routable);

      $this->routingLogger->write ("<#row>Entering router #<b>$key</b> $c</#row><#indent>");

      return $this->logMiddlewareBlock (
        function ($req, $res, $nx) use ($key, $routable) {
          return parent::iteration_stepMatchMiddleware ($key, $routable, $req, $res, $nx);
        },
        $request, $response, $next, "<#row>Exiting router with <b>no route found</b></#row>",
        "</#indent><#row>Exiting router and returning the response to the previous middlware</#row>", true);
    }
    return parent::iteration_stepMatchMiddleware ($key, $routable, $request, $response, $next);
  }

}
