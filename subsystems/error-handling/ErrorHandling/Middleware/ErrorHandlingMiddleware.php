<?php
namespace Electro\ErrorHandling\Middleware;

use Electro\Interfaces\Http\ErrorRendererInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Kernel\Config\KernelSettings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Handles errors that occur throughout the HTTP request handling pipeline.
 *
 * <p>Exceptions / errors that are thrown further the pipeline will be catched here and transformed into normal HTTP
 * responses that will resume travelling the pipeline from this point backwards.
 *
 * <p>Normal HTTP responses with status code >= 400 are processed by the error renderer service to generate an error
 * representation suitable for the request's HTTP client.
 *
 * <p>**Note:** every request handler that follows this one on the pipeline should wrap it's request handling/forwarding
 * code in a `try/finally` block if it really needs to do something after the request is handled/delegated (ex.
 * finishing an open tag on the debug console), otherwise that code may not execute if an exception is thrown or an
 * error occurs.
 *
 * <p>Ex:
 *
 *       $logger->log ("<div>");
 *       try {
 *         return $next ();
 *       }
 *       finally {
 *         $logger->log ("</div>");
 *       }
 */
class ErrorHandlingMiddleware implements RequestHandlerInterface
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
