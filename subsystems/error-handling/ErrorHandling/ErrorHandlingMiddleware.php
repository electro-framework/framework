<?php
namespace Selenia\ErrorHandling;

use PhpKit\WebConsole\ErrorHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Selenia\Application;
use Selenia\Exceptions\HttpException;
use Selenia\Http\HttpUtil;
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

      // On debug mode, a debugging error popup is displayed for HTML responses.

      if ($app->debugMode && HttpUtil::clientAccepts ($request, 'text/html'))
        return ErrorHandler::showErrorPopup ($error, $response);

      // Otherwise, exceptions are shown as a panel (for HTML responses) or they are encoded
      // into one of the supported output formats.

      $response = $this->responseMaker->makeStream ();
      $body     = $response->getBody ();

      // The message is assumed to be a plain, one-line string (no formatting)
      $message = $error->getMessage ();
      $status  = $error->getCode ();

      if (HttpUtil::clientAccepts ($request, 'text/html')) {
        $response = $response->withHeader ('Content-Type', 'text/html');
        $body->write ("<!DOCTYPE html>
<html>
  <head>
    <title>$message</title>
    <style>
      body {font-family:sans-serif;background:#CCC}
      kbd {color:#00C;font-size:15px;font-family:menlo,sans-serif}
      h5 {color:#999}
      .panel {max-width:800px;margin:50px auto;padding:0 30px 30px;background:#FFF;border-radius:10px;box-shadow:3px 3px 10px rgba(0,0,0,0.4)}
    </style>
  </head>
  <body>
    <div class='panel'>");

        if ($error instanceof HttpException)
          $body->write ("
      <h5 style='float:left'>HTTP $status</h5>
      <h5 style='float:right'>$app->appName</h5>
      <h1 style='clear:both' align=center>$message</h1>&nbsp;
      <p align=center>$error->info</p>");

        else $body->write ("
      <h5 style='float:right'>$app->appName</h5>
      <h1 style='clear:both' align=center>$message</h1>&nbsp;
      <p align=center>$error->info</p>");

        $body->write ("
    </div>
  </body>
</html>");
      }
      else if (HttpUtil::clientAccepts ($request, 'text/plain') || self::clientAccepts ($request, '*/*')) {
        $response = $response->withHeader ('Content-Type', 'text/plain');
        $body->write ($message);
      }
      else if (HttpUtil::clientAccepts ($request, 'application/json')) {
        $response = $response->withHeader ('Content-Type', 'application/json');
        $body->write (json_encode (['error' => ['code' => $status, 'message' => $message]]));
      }
      else if (HttpUtil::clientAccepts ($request, 'application/xml')) {
        $response = $response->withHeader ('Content-Type', 'application/xml');
        $body->write ("<?xml version=\"1.0\"?><error><code>$status</code><message>$message</message></error>");
      }

      return $response->withStatus ($status, $message);
    }
  }

}
