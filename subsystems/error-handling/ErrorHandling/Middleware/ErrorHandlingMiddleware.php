<?php
namespace Selenia\ErrorHandling\Middleware;

use PhpKit\WebConsole\ErrorConsole\ErrorConsole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Selenia\Application;
use Selenia\Exceptions\HttpException;
use Selenia\Http\HttpUtil;
use Selenia\Interfaces\Http\ErrorRendererInterface;
use Selenia\Interfaces\Http\RequestHandlerInterface;

/**
 * Handles errors that occur throughout the HTTP request handling pipeline.
 *
 * <p>Exceptions / errors that are thrown further the pipeline will be catched here and transformed into normal HTTP
 * responses that will resume travelling the pipeline from this point backwards.
 *
 * <p>**Note:** every request handler that follows this one on the pipeline should wrap it's request
 * handling/forwarding
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
      return $next();
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

      return $this->errorRenderer->render($request, $error);
    }
  }

}
