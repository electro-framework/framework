<?php

namespace Electro\ContentRepository\Middleware;

use Electro\ContentRepository\Config\ContentRepositorySettings;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Http\ResponseFactoryInterface;
use Electro\Kernel\Config\KernelSettings;
use League\Glide\Server;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Serves static files from the content repository.
 */
class ContentServerMiddleware implements RequestHandlerInterface
{
  /**
   * @var KernelSettings
   */
  private $app;
  /**
   * @var Server
   */
  private $glideServer;
  /**
   * @var ResponseFactoryInterface
   */
  private $responseFactory;
  /**
   * @var ContentRepositorySettings
   */
  private $settings;

  function __construct (ResponseFactoryInterface $responseFactory, Server $glideServer,
                        ContentRepositorySettings $settings)
  {
    $this->responseFactory = $responseFactory;
    $this->glideServer     = $glideServer;
    $this->settings        = $settings;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $url = $request->getAttribute ('virtualUri', '');
    if (!str_beginsWith ($url, "{$this->settings->fileBaseUrl}/"))
      return $next ();

    // Strip prefix from URL
    $url    = substr ($url, strlen ($this->settings->fileBaseUrl) + 1);
    $params = $request->getQueryParams ();

    // If the request requests a resized image:

    if (isset($params['w']) || isset($params['h'])) {
      $cachedPath = $this->glideServer->makeImage ($url, $params); // Generates a new image ONLY if it isn't cached yet.
      $cache      = $this->glideServer->getCache ();
      return $this->outputFile (
        $cache->readStream ($cachedPath),
        $cache->getSize ($cachedPath),
        $cache->getMimetype ($cachedPath)
      );
    }

    // Otherwise, serve the source file (if it exists).

    if ($this->glideServer->sourceFileExists ($url)) {
      $source = $this->glideServer->getSource ();
      return $this->outputFile (
        $source->readStream ($url),
        $source->getSize ($url),
        $source->getMimetype ($url)
      );
    }

    return $this->responseFactory->make (404, "Not found: $url", 'text/plain');
  }

  private function outputFile ($stream, $size, $mime)
  {
    return $this->responseFactory->makeFromStream ($stream, 200, [
      'Content-Type'   => $mime,
      'Content-Length' => $size,
      'Cache-Control'  => 'max-age=31536000, public',
      'Expires'        => date_create ('+1 years')->format ('D, d M Y H:i:s') . ' GMT',
    ]);
  }

}
