<?php
namespace Electro\ContentServer\Middleware;

use Electro\ContentServer\Config\ContentServerSettings;
use Electro\ContentServer\Lib\FileUtil;
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
   * @var ContentServerSettings
   */
  private $settings;

  function __construct (ResponseFactoryInterface $responseFactory, Server $glideServer, ContentServerSettings $settings)
  {
    $this->responseFactory = $responseFactory;
    $this->glideServer     = $glideServer;
    $this->settings        = $settings;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $url = $request->getAttribute ('virtualUri', '');
    if (!str_beginsWith ($url, "{$this->settings->fileBaseUrl()}/"))
      return $next ();

    // Strip prefix from URL
    $url = substr ($url, strlen ($this->settings->fileBaseUrl ()) + 1);

    $path = "{$this->settings->fileArchivePath()}/$url";

    if (!file_exists ($path))
      return $this->responseFactory->make (404, "Not found: $path", 'text/plain');

    $mime = FileUtil::getMimeType ($path);

    // Serve image file.

    if (FileUtil::isImageType ($mime))
      // Use image manipulation parameters extracted from the request.
      return $this->glideServer->getImageResponse ($url, $request->getQueryParams ());

    // Server non-image file.

    return $this->responseFactory->makeFromStream (fopen ($path, 'rb'), 200, [
      'Content-Type'   => $mime,
      'Content-Length' => (string)filesize ($path),
      'Cache-Control'  => 'max-age=31536000, public',
      'Expires'        => date_create ('+1 years')->format ('D, d M Y H:i:s') . ' GMT',
    ]);

  }

}
