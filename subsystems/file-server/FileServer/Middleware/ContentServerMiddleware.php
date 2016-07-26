<?php
namespace Electro\FileServer\Middleware;

use League\Glide\Server;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Electro\Application;
use Electro\FileServer\Lib\FileUtil;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Http\ResponseFactoryInterface;

/**
 * Serves static files from the content repository.
 */
class ContentServerMiddleware implements RequestHandlerInterface
{
  /**
   * @var Application
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

  function __construct (Application $app, ResponseFactoryInterface $responseFactory, Server $glideServer)
  {
    $this->app             = $app;
    $this->responseFactory = $responseFactory;
    $this->glideServer     = $glideServer;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $url = $request->getAttribute ('virtualUri', '');
    if (!str_beginsWith ($url, "{$this->app->fileBaseUrl}/"))
      return $next ();

    // Strip prefix from URL
    $url = substr ($url, strlen ($this->app->fileBaseUrl) + 1);

    $path = "{$this->app->fileArchivePath}/$url";

    if (!file_exists ($path))
      return $this->responseFactory->make (404, "Not found: $path", 'text/plain');

    $mime = FileUtil::getMimeType ($path);

    // Serve image file.

    if (FileUtil::isImageType ($mime))
      // Use image manipulation parameters extracted from the request.
      return $this->glideServer->getImageResponse ($url, $request->getQueryParams ());

    // Server non-image file.

    return $this->responseFactory->makeStream (fopen ($path, 'rb'), 200, [
      'Content-Type'   => $mime,
      'Content-Length' => (string)filesize ($path),
      'Cache-Control'  => 'max-age=31536000, public',
      'Expires'        => date_create ('+1 years')->format ('D, d M Y H:i:s') . ' GMT',
    ]);

  }

}
