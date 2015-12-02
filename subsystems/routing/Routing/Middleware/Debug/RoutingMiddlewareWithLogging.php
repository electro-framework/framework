<?php
namespace Selenia\Routing\Middleware\Debug;

use PhpKit\WebConsole\DebugConsole\DebugConsole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Interfaces\Http\Shared\ApplicationRouterInterface;
use Selenia\Routing\Services\Debug\MiddlewareStackWithLogging;

/**
 * Performs the application's HTTP request routing.
 *
 * <p>{@see MainRouterInterface} is usually an injection alias of this class.
 */
class RoutingMiddlewareWithLogging extends MiddlewareStackWithLogging
  implements ApplicationRouterInterface /* for call-signature compatibility */
{
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {

    try {
      /** @var ServerRequestInterface $request */
      $request = $request->withRequestTarget ($request->getAttribute ('virtualUri'));

      $res = parent::__invoke ($request, $response, $next);

    }
    finally {
//echo $this->routingLogger->getContent ();
//echo $this->routingLogger->render ();
//      exit;
//      DebugConsole::logger ('routes')
//                  ->write ($this->routingLogger->getContent ())
//                  ->write ("</#section>");
    }
    return $res;
  }

}
