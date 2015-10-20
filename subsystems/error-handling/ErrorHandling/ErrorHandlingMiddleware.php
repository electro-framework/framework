<?php
namespace Selenia\ErrorHandling;

use PhpKit\WebConsole\ErrorHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Selenia\Application;
use Selenia\Exceptions\FlashMessageException;
use Selenia\Exceptions\HttpException;
use Selenia\FlashExceptions\SessionException;
use Selenia\Interfaces\MiddlewareInterface;
use Selenia\Interfaces\ResponseMakerInterface;

/**
 * Handles errors that occur throughout the HTTP middleware stack.
 */
class ErrorHandlingMiddleware implements MiddlewareInterface
{
  private $app;
  private $logger;
  private $responseMaker;

  function __construct (Application $app, LoggerInterface $logger, ResponseMakerInterface $responseMaker)
  {
    $this->app           = $app;
    $this->logger        = $logger;
    $this->responseMaker = $responseMaker;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    try {
      return $next();
    } catch (\Exception $error) {
      $app = $this->app;

      $this->logger->error ($error->getMessage (),
        ['stackTrace' => str_replace ("$app->baseDirectory/", '', $error->getTraceAsString ())]
      );

      if ($error instanceof HttpException) {
        $response = $this->responseMaker->make ();
        $response->getBody ()->write ("<!DOCTYPE html>
<html>
  <head>
    <title>{$error->getMessage()}</title>
    <style>
      body {font-family:sans-serif;background:#CCC}
      kbd {color:#00C;font-size:15px;font-family:menlo,sans-serif}
      h5 {color:#999}
      .panel {max-width:800px;margin:50px auto;padding:0 30px 30px;background:#FFF;border-radius:10px;box-shadow:3px 3px 10px rgba(0,0,0,0.4)}
    </style>
  </head>
  <body>
    <div class='panel'>
      <h5 style='float:left'>HTTP {$error->getCode()}</h5>
      <h5 style='float:right'>$app->appName</h5>
      <h1 style='clear:both' align=center>{$error->getMessage()}</h1>&nbsp;
      <p align=center>$error->info</p>
    </div>
  </body>
</html>");
        return $response->withStatus ($error->getCode (), $error->getMessage ());
      }
      return ErrorHandler::showErrorPopup ($error, $response);
    }
  }

}
