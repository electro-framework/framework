<?php
namespace Selenia\ErrorHandling\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Selenia\Application;
use Selenia\Interfaces\Http\ErrorRendererInterface;
use Selenia\Interfaces\Http\RequestHandlerInterface;

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
   * @var Application
   */
  private $app;
  /**
   * @var ErrorRendererInterface
   */
  private $errorRenderer;
  /**
   * @var LoggerInterface
   */
  private $logger;

  function __construct (LoggerInterface $logger, ErrorRendererInterface $errorRenderer, Application $app)
  {
    $this->logger        = $logger;
    $this->errorRenderer = $errorRenderer;
    $this->app           = $app;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    try {
      /** @var ResponseInterface $response */
      $response = $next();

      // Responses with status >= 400 will be converted into a "pretty" format, using the ErrorRenderer
      // service, which supports multiple output formats, depending on the HTTP client expectations.
      $status = $response->getStatusCode();
      if ($status >= 400)
        return $this->errorRenderer->render($request, $response);

      return $response;
    }
    catch (\Exception $error) {
      $app = $this->app;

      // First, start by logging the error to the application logger.

      $this->logger->error ($error->getMessage (),
        [
          'exception'  => $error, // PSR-3-compliant property
          'stackTrace' => str_replace ("$app->baseDirectory/", '', $error->getTraceAsString ()),
        ]
      );
      return $this->errorRenderer->render($request, $response, $error);
    }
  }

}
