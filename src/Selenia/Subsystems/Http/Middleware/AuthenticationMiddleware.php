<?php
namespace Selenia\Subsystems\Http\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Selenia\Subsystems\Http\Contracts\MiddlewareInterface;

/**
 *
 */
class AuthenticationMiddleware implements MiddlewareInterface
{
  function __invoke (RequestInterface $request, ResponseInterface $response, callable $next)
  {
  }
}
