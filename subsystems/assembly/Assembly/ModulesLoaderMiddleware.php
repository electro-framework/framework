<?php
namespace Selenia\Subsystems\Http\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Selenia\Interfaces\MiddlewareInterface;

/**
 *
 */
class ModulesLoaderMiddleware implements MiddlewareInterface
{
  function __invoke (RequestInterface $request, ResponseInterface $response, callable $next)
  {
  }
}
