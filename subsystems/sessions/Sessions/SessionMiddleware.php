<?php
namespace Selenia\Sessions;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Selenia\Interfaces\MiddlewareInterface;

/**
 *
 */
class SessionMiddleware implements MiddlewareInterface
{
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    return $next();
  }
}
