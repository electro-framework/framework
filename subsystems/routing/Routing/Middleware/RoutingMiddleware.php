<?php
namespace Electro\Routing\Middleware;

use Electro\Debugging\Config\DebugSettings;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\RouteMatcherInterface;
use Electro\Interfaces\Http\Shared\ApplicationRouterInterface;
use Electro\Interfaces\Http\Shared\CurrentRequestInterface;
use Electro\Routing\Services\MiddlewareStack;
use Electro\Routing\Services\RoutingLogger;
use PhpKit\WebConsole\Lib\Debug;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Performs the application's HTTP request routing.
 *
 * <p>{@see ApplicationRouterInterface} is usually an injection alias of this class.
 *
 * <br>
 * > **Note:** unline {@see BaseRouter} this class provides its own route trace logging; it's not dependent on a
 * subclass like {@see BaseRouterWithLogging}.
 */
class RoutingMiddleware extends MiddlewareStack
  implements ApplicationRouterInterface /* for call-signature compatibility */

{
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

    return parent::__invoke ($request, $response, $next);
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

      $matched = true;
      $this->routingLogger->write ("<#row>Entering router #<b>$key</b> $c</#row><#indent>");

      try {
        $res = parent::iteration_stepMatchMiddleware ($key, $routable, $request, $response,
          function (...$args) use ($next, &$matched) {
            $matched = false;
            $this->routingLogger->write ("</#indent><#row>Exiting router with <b>no route found</b></#row>");
            return $next (...$args);
          });
        if ($matched)
          $this->routingLogger->write ("</#indent><#row>Exiting router</#row>");

        return $res;
      }
      catch (\Throwable $e) {
        $this->routingLogger->write ("</#indent>");
        throw $e;
      }
      catch (\Exception $e) {
        $this->routingLogger->write ("</#indent>");
        throw $e;
      }
    }
    return parent::iteration_stepMatchMiddleware ($key, $routable, $request, $response, $next);
  }

}
