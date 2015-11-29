<?php
namespace Selenia\Routing\Services\Debug;

use PhpKit\WebConsole\DebugConsole\DebugConsole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Performs the application's HTTP request routing.
 *
 * <p>{@see MainRouterInterface} is usually an injection alias of this class.
 */
class RoutingMiddlewareWithLogging extends MiddlewareStackWithLogging
{
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $rootR = $this->handlers
      ? implode ('', map ($this->handlers, function ($r) {
        return sprintf ('<#i|__rowHeader><#type>%s</#type></#i>', typeOf ($r));
      }))
      : '<#i><i>empty</i></#i>';

    DebugConsole::logger ('routes')
                ->write ("<#section|Router pipeline's content>$rootR</#section>" .
                         "<#section|Application's middleware pipeline run history>" .
                         "<#i|__rowHeader><i>(previous middlewares are omitted because they can't be traced)</i></#i>");

    try {
      /** @var ServerRequestInterface $request */
      $request = $request->withRequestTarget ($request->getAttribute ('virtualUri'));

      $res = parent::__invoke ($request, $response, $next);
    }
    finally {
      DebugConsole::logger ('routes')->write ($this->routingLogger->getContent () . "</#section>");
    }
    return $res;
  }

}
