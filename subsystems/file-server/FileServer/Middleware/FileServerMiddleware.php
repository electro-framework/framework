<?php
namespace Selenia\FileServer\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\FileServer\Services\FileServerMappings;
use Selenia\Interfaces\MiddlewareInterface;
use Selenia\Interfaces\ResponseFactoryInterface;

/**
 * Serves static assets on virtual URLs exposed from packages or from the framework itself.
 */
class FileServerMiddleware implements MiddlewareInterface
{
  static private $MIME_TYPES = [
    'js'    => 'application/javascript',
    'css'   => 'text/css',
    'woff'  => 'application/font-woff',
    'woff2' => 'application/font-woff2',
    'ttf'   => 'font/ttf',
    'otf'   => 'font/otf',
    'eot'   => 'application/vnd.ms-fontobject',
    'jpg'   => 'image/jpeg',
    'png'   => 'image/png',
    'gif'   => 'image/gif',
  ];
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
    if ($isMapped) {

      // Run PHP files

      if (file_exists ($x = "$path.php")) {
        try {
          ob_start ();
          require $x;
          $response->getBody ()->write (ob_get_clean ());
        } catch (\Exception $e) {
          @ob_get_clean ();
          throw $e;
        }
        return $response;
      }

      // Serve static files

      $type     = get (self::$MIME_TYPES, substr ($path, strrpos ($path, '.') + 1), 'application/octet-stream');
      $response = $response->withHeader ('Content-Type', $type);
      if (!$this->app->debugMode) {
        $response = $response
          ->withHeader ('Expires', gmdate ('D, d M Y H:i:s \G\M\T', time () + 36000))// add 10 hours
          ->withHeader ('Cache-Control', 'public, max-age=36000');
      }
      if (!file_exists ($path))
        return $this->responseFactory->make (404, "Not found: $path", 'text/plain');
      return $response->withBody ($this->responseFactory->makeBody ('', fopen ($path, 'rb')));
    }

    return $next();
  }

}
