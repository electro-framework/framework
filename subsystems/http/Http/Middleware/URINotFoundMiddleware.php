<?php
namespace Selenia\Http\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Exceptions\HttpException;
use Selenia\Interfaces\Http\RequestHandlerInterface;

/**
 *
 */
class URINotFoundMiddleware implements RequestHandlerInterface
{
  private $app;

  function __construct (Application $app)
  {
    $this->app = $app;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    throw new HttpException (404, 'Page not available',
      "The requested web address <kbd>{$request->getUri()->getPath()}</kbd> is not valid");
  }
}
