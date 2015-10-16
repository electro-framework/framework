<?php
namespace Selenia\Routing\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Interfaces\MiddlewareInterface;

/**
 *
 */
class URINotFoundMiddleware implements MiddlewareInterface
{
  private $app;

  function __construct (Application $app)
  {
    $this->app = $app;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $response->getBody ()
             ->write ("<h1>Not Found</h1><p>The requested virtual URL <code><big>{$this->app->baseURI}/<b>{$request->getAttribute('VURI')}</b></big></code> was not found on this server.</p>");
    return $response->withStatus (404, 'Not Found');
  }
}
