<?php
namespace Selenia\Http\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Http\Lib\Http;
use Selenia\Interfaces\Http\RequestHandlerInterface;

/**
 * A middleware that generates a 404 Not Found response.
 */
class URLNotFoundMiddleware implements RequestHandlerInterface
{
  private $app;

  function __construct (Application $app)
  {
    $this->app = $app;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $path = $request->getUri ()->getPath ();
    $base = $request->getAttribute ('baseUri', '');
    $l    = strlen ($base);
    if ($l && substr ($path, 0, $l) == $base)
      $path = substr ($path, $l);
    return Http::send ($response, 404, "Invalid URL", "Virtual URL: <kbd>$path</kbd>");
  }
}
