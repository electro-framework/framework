<?php
namespace Selenia\Http\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Interfaces\Http\MiddlewareInterface;
use Selenia\Interfaces\Http\ResponseFactoryInterface;

/**
 * Applies gzip compression to the HTTP response.
 */
class CompressionMiddleware implements MiddlewareInterface
{
  function __construct (ResponseFactoryInterface $responseFactory)
  {
    $this->responseFactory = $responseFactory;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    /** @var ResponseInterface $response */
    $response = $next();

    if (strpos ($request->getHeaderLine ('accept-encoding'), 'gzip') !== false) {
      $out = gzencode ($response->getBody (), 1);
      return $response
        ->withHeader ('Content-Encoding', 'gzip')
        ->withBody ($this->responseFactory->makeBody ($out));
    }
    return $response;
  }

}
