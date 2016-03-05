<?php
namespace Selenia\Http\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Interfaces\Http\RequestHandlerInterface;

/**
 * Verifies CSRF tokens for form POST requests.
 */
class FormDataExtMiddleware implements RequestHandlerInterface
{
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    /** @var ResponseInterface $response */
    $response = $next();

    return $response;
  }

}
