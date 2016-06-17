<?php
namespace Electro\FileServer\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Electro\Application;
use Electro\FileServer\Lib\FileUtil;
use Electro\FileServer\Services\FileServerMappings;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Http\ResponseFactoryInterface;

/**
 * Serves static assets on virtual URLs exposed from packages or from the framework itself.
 */
class FileServerMiddleware implements RequestHandlerInterface
{
  /**
   * @var Application
   */
  private $app;
  /**
   * @var FileServerMappings
   */
  private $mappings;
  /**
   * @var ResponseFactoryInterface
   */
  private $responseFactory;

  function __construct (Application $app, ResponseFactoryInterface $responseFactory, FileServerMappings $mappings)
  {
    $this->app             = $app;
    $this->responseFactory = $responseFactory;
    $this->mappings        = $mappings;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $URI  = $request->getAttribute ('virtualUri', '');
    $path = $this->mappings->toFilePath ($URI, $isMapped);
    if (!$isMapped)
      return $next();

    // Run PHP files (the .php extension is not present on the URL)

    if (file_exists ($x = "$path.php")) {
      try {
        ob_start ();
        require $x;
        $response->getBody ()->write (ob_get_clean ());
      }
      catch (\Exception $e) {
        @ob_get_clean ();
        throw $e;
      }
      return $response;
    }

    // Serve static files

    if (!file_exists ($path))
      return $this->responseFactory->make (404, "Not found: $path", 'text/plain');

    return $this->responseFactory->makeStream (fopen ($path, 'rb'), 200, [
      'Content-Type'   => FileUtil::getMimeType ($path),
      'Content-Length' => (string)filesize ($path),
      'Cache-Control'  => 'max-age=3600, public',
      'Expires'        => date_create ('+1 hour')->format ('D, d M Y H:i:s') . ' GMT',
    ]);
  }

}
