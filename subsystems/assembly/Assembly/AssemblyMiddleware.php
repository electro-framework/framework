<?php
namespace Selenia\Assembly;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Selenia\Interfaces\MiddlewareInterface;

/**
 *
 */
class AssemblyMiddleware implements MiddlewareInterface
{
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    return $next();
  }
}
