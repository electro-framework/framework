<?php
namespace Electro\ErrorHandling\Middleware;

use Electro\Interfaces\Http\ErrorRendererInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Kernel\Config\KernelSettings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Handles a special HTTP request that opens a file for editing on an IDE / texteditor.
 * <p>Requests of this type are usually triggered by the developer clickng on an error location link on the error console.
 *
 * ><p>For security reasons, this is only enabled on **debug mode**.
 */
class EditorLauncherMiddleware implements RequestHandlerInterface
{
  /**
   * @var ErrorRendererInterface
   */
  private $errorRenderer;
  /**
   * @var KernelSettings
   */
  private $kernelSettings;
  /**
   * @var LoggerInterface
   */
  private $logger;

  function __construct (LoggerInterface $logger, ErrorRendererInterface $errorRenderer, KernelSettings $kernelSettings)
  {
    $this->logger         = $logger;
    $this->errorRenderer  = $errorRenderer;
    $this->kernelSettings = $kernelSettings;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    try {
      /** @var ResponseInterface $response */
      $response = $next();

      // Responses with status >= 400 will be converted into a "pretty" format, using the ErrorRenderer
      // service, which supports multiple output formats, depending on the HTTP client expectations.
      $status = $response->getStatusCode ();
      if ($status >= 400)
        return $this->errorRenderer->render ($request, $response);

      return $response;
    }
    catch (\Exception $error) {
      $app = $this->kernelSettings;

      // First, start by logging the error to the application logger.
      $this->logger->error ($error->getMessage (),
        [
          'exception'  => $error, // PSR-3-compliant property
          'stackTrace' => str_replace ("$app->baseDirectory/", '', $error->getTraceAsString ()),
        ]
      );

      // Discard possibly incomplete application output.
      while (ob_get_level ()) ob_end_clean ();

      // Now, output the error description.
      return $this->errorRenderer->render ($request, $response, $error);
    }
  }

}
