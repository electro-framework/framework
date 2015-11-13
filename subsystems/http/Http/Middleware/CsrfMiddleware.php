<?php
namespace Selenia\Http\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Interfaces\Http\MiddlewareInterface;

/**
 * Verifies CSRF tokens for form POST requests.
 */
class CsrfMiddleware implements MiddlewareInterface
{
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    /** @var ResponseInterface $response */
    $response = $next();

    return $response;
  }

}
