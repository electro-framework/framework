<?php
namespace Electro\Routing\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;

/**
 * Matches the URL agains a map of predefined permalinks and, if a match is found, replaces the URL internally for
 * routing.
 *
 * <p>You should register this middleware right before the router.
 */
class PermalinksMiddleware implements RequestHandlerInterface
{

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $URL = $request->getAttribute ('virtualUri');

    return $next ();
  }

}
