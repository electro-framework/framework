<?php
namespace Electro\Http\Middleware;

use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Http\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Applies gzip compression to the HTTP response.
 *
 * The response will be compressed only if its content type is a text-based format and the client declares support for
 * GZIP compression.
 */
class CompressionMiddleware implements RequestHandlerInterface
{
  /**
   * Note: all content types beginning with 'text/' or ending in 'xml' (ex: 'application/xml' or 'application/xhtml+xml')
   * are already supported, so they are not included here.
   */
  const ADDITIONAL_CONTENT_TYPES = [
    'application/json',
  ];

  function __construct (ResponseFactoryInterface $responseFactory)
  {
    $this->responseFactory = $responseFactory;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    /** @var ResponseInterface $response */
    $response = $next();

    if (strpos ($request->getHeaderLine ('accept-encoding'), 'gzip') !== false) {
      $contentType = $response->getHeaderLine ('content-type') ?? '';
      if (substr($contentType, 0, 5) == 'text/' || substr($contentType, -3) == 'xml' ||
          in_array ($contentType, self::ADDITIONAL_CONTENT_TYPES)) {
        $uncompressed = $response->getBody ();
        $out = gzencode ($uncompressed, 1);
        //If the compression failed, return the uncompressed response.
        if ($out === false)
          $out = $uncompressed;
        return $response
          ->withHeader ('Content-Encoding', 'gzip')
          ->withHeader ('Content-Length', strlen ($out))
          ->withBody ($this->responseFactory->makeBodyStream ($out));
      }
    }
    return $response;
  }

}
