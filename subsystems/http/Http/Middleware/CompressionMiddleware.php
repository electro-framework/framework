<?php
namespace Selenia\Http\Middleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Selenia\Interfaces\MiddlewareInterface;

/**
 * Applies gzip compression to the HTTP response.
 */
class CompressionMiddleware implements MiddlewareInterface
{
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    return $next();
  }
}
