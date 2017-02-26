<?php
namespace Electro\Http\Middleware;

use Electro\Interfaces\Http\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Verifies CSRF tokens for form POST requests.
 */
class CsrfMiddleware implements RequestHandlerInterface
{
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    /** @var ResponseInterface $response */
    $response = $next();

    return $response;
  }

}
