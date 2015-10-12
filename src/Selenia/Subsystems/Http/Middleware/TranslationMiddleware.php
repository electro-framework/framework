<?php
namespace Selenia\Subsystems\Http\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Selenia\Subsystems\Http\Contracts\MiddlewareInterface;

/**
 * Post-processes the HTTP response to replace translation keys by the corresponding translation.
 */
class TranslationMiddleware implements MiddlewareInterface
{
  function __invoke (RequestInterface $request, ResponseInterface $response, callable $next)
  {
  }
}
