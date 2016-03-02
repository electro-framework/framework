<?php
namespace Selenia\FileServer\Middleware;

use League\Glide\ServerFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Interfaces\Http\RequestHandlerInterface;

/**
 * Serves static assets on virtual URLs exposed from packages or from the framework itself.
 */
class MediaServerMiddleware implements RequestHandlerInterface
{
  /**
   * @var Application
   */
  private $app;

  function __construct (Application $app)
  {
    $this->app = $app;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $url = $request->getAttribute ('virtualUri', '');
    if (!str_beginsWith ($url, "{$this->app->fileBaseUrl}/"))
      return $next ();

    // Strip prefix from URL
    $url = substr ($url, strlen ($this->app->fileBaseUrl) + 1);

    // Setup Glide server
    $server = ServerFactory::create([
      'source' => $this->app->fileArchivePath,
      'cache' => $this->app->imagesCachePath
    ]);

    // Use information from the request
    $server->outputImage($url, $request->getQueryParams());
  }

}
