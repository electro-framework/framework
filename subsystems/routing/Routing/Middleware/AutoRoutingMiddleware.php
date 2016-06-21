<?php
namespace Electro\Routing\Middleware;

use Electro\Exceptions\Fatal\FileNotFoundException;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * It allows a designer to rapidly prototype the application by automatically providing routing for URLs matching files
 * on the views directories, which will be routed to a generic controller that will load the matched view.
 *
 * <p>**Note:** currently, this middleware only supports Matisse templates.
 *
 * <p>**This is NOT recommended for production!**
 *
 * <p>You should register this middleware right before the router, but only if `debugMode = false`.
 */
class AutoRoutingMiddleware implements RequestHandlerInterface
{

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $URL = $request->getAttribute ('virtualUri');
    if ($URL == '') $URL = 'index';
    elseif (substr ($URL, -1) == '/') $URL = $URL . 'index';

    try {
      return page ($URL);
    }
    catch (FileNotFoundException $e) {
      return $next ();
    }
  }

}
