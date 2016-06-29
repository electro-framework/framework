<?php
namespace Electro\Routing\Middleware;

use Electro\Interfaces\Http\Shared\ApplicationRouterInterface;
use Electro\Routing\Services\MiddlewareStack;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
    // Note: the following change to the Request is router-specific and it's not required by other middleware.
    // Only the router uses a mutating requestTarget to handle sub-routes.
    // TODO: route using UriInterface and do not change requestTarget.

    /** @var ServerRequestInterface $request */
    $request = $request->withRequestTarget (either ($request->getAttribute ('virtualUri'), '.'));

    //----------

    return parent::__invoke ($request, $response, $next);
  }

}
