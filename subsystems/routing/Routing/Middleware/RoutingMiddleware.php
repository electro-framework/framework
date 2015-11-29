<?php
namespace Selenia\Routing\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Routing\Services\MiddlewareStack;

/**
 * Performs the application's HTTP request routing.
 *
 * <p>{@see MainRouterInterface} is usually an injection alias of this class.
 */
class RoutingMiddleware extends MiddlewareStack
{
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    /** @var ServerRequestInterface $request */
    $request = $request->withRequestTarget ($request->getAttribute ('virtualUri'));

    return parent::__invoke ($request, $response, $next);
  }

}
