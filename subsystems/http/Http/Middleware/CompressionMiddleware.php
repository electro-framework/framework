<?php

namespace Electro\Http\Middleware;

use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Http\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Applies gzip compression to the HTTP response, if appropriate.
 *
 * The response will be compressed only if the client declares support for GZIP compression, the response content type
 * is a text-based format with size > 1000 and the status code is in the 200..300 range.
 *
 * >Note: short responses that may fit into a network packet are not worth compressing.
 *
 * >Note: gzip is used and not deflate because historically, "deflate" was problematic because early Microsoft IIS servers
 * >would send raw deflate data instead of zlib data. To work around that, it became common to just use gzip.
 */
class CompressionMiddleware implements RequestHandlerInterface
{
  /**
   * Note: all content types beginning with 'text/' or ending in 'xml' (ex: 'application/xml' or
   * 'application/xhtml+xml') are already supported, so they are not included here.
   */
  const ADDITIONAL_CONTENT_TYPES = [
    'application/json',
  ];
  const MIN_CONTENT_SIZE         = 1000;
  /**
   * @var ResponseFactoryInterface
   */
  private $responseFactory;

  function __construct (ResponseFactoryInterface $responseFactory)
  {
    $this->responseFactory = $responseFactory;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    /** @var ResponseInterface $response */
    $response = $next();

    $status = $response->getStatusCode ();
    if ($status >= 200 && $status < 300 && strpos ($request->getHeaderLine ('accept-encoding'), 'gzip') !== false) {
      $contentType = $response->getHeaderLine ('content-type') ?? '';
      if (substr ($contentType, 0, 5) == 'text/' || substr ($contentType, -3) == 'xml' ||
          in_array ($contentType, self::ADDITIONAL_CONTENT_TYPES)) {
        $uncompressed = $response->getBody ();
        if (strlen ($uncompressed) >= self::MIN_CONTENT_SIZE) {
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
    }
    return $response;
  }

}
