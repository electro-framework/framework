<?php
namespace Selenia\ErrorHandling;

use PhpKit\WebConsole\ErrorHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Selenia\Application;
use Selenia\Interfaces\MiddlewareInterface;

/**
 *
 */
class ErrorHandlingMiddleware implements MiddlewareInterface
{
  private $app;
  private $logger;

  function __construct (Application $app, LoggerInterface $logger)
  {
    $this->app    = $app;
    $this->logger = $logger;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    try {
      return $next();
    } catch (\Exception $error) {
      $this->logger->error ($error->getMessage (),
        ['stackTrace' => str_replace ("{$this->app->baseDirectory}/", '', $error->getTraceAsString ())]
      );
      return ErrorHandler::showErrorPopup ($error, $response);

    }
  }
}
