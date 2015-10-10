<?php
namespace Selenia\Http\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Selenia\Http\Contracts\MiddlewareInterface;

/**
 *
 */
class SessionMiddleware implements MiddlewareInterface
{
  function __invoke (RequestInterface $request, ResponseInterface $response, callable $next)
  {
  }
}
