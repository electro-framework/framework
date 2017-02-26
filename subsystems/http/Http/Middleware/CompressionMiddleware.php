<?php
namespace Electro\Http\Middleware;

use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Http\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Applies gzip compression to the HTTP response.
 */
class CompressionMiddleware implements RequestHandlerInterface
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
        ->withBody ($this->responseFactory->makeBodyStream ($out));
    }
    return $response;
  }

}
