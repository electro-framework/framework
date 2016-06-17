<?php
namespace Electro\Routing\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Electro\Interfaces\Http\Shared\ApplicationRouterInterface;
use Electro\Routing\Services\MiddlewareStack;

/**
 * Performs the application's HTTP request routing.
 *
 * <p>{@see MainRouterInterface} is usually an injection alias of this class.
 */
class RoutingMiddleware extends MiddlewareStack
  implements ApplicationRouterInterface /* for call-signature compatibility */
{
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    /** @var ServerRequestInterface $request */
    $request = $request->withRequestTarget (either($request->getAttribute ('virtualUri'),'.'));

    return parent::__invoke ($request, $response, $next);
  }

}
