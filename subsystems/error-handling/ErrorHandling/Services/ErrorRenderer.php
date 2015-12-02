<?php
namespace Selenia\ErrorHandling\Services;

use PhpKit\WebConsole\ErrorConsole\ErrorConsole;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Exceptions\HttpException;
use Selenia\Http\HttpUtil;
use Selenia\Interfaces\Http\ErrorRendererInterface;
use Selenia\Interfaces\Http\ResponseFactoryInterface;

/**
 * Renders an error HTTP response into a format supported by the client.
 */
class ErrorRenderer implements ErrorRendererInterface
{
protected $app;
private $responseFactory;

function __construct (Application $app, ResponseFactoryInterface $responseFactory)
{
  $this->app             = $app;
  $this->responseFactory = $responseFactory;
}

function render (ServerRequestInterface $request, $error)
{
  $app = $this->app;

  // Now, create an HTTP response.

  $response = $this->responseFactory->makeStream ('php://memory',
    $error instanceof HttpException ? $error->getCode () : 500);

  // On debug mode, a debugging error popup is displayed for HTML responses.

  $status = $error instanceof HttpException ? $error->getCode () : 0;
  if ($app->debugMode
      && HttpUtil::clientAccepts ($request, 'text/html')
      && (!$status || $status != 404)
  )
    return ErrorConsole::display ($error, $response);

  // Otherwise, exceptions are shown as a panel (for HTML responses) or they are encoded
  // into one of the supported output formats.

  $body = $response->getBody ();

  // The message is assumed to be a plain, one-line string (no formatting). If not, make it so.
  $message = strip_tags ($error->getMessage ());
  // Http errors may contain an additional `info` property with extended error information.
  $info = property ($error, 'info', '');

  if (HttpUtil::clientAccepts ($request, 'text/html')) {
    $response = $response->withHeader ('Content-Type', 'text/html');
    ob_start ();
    $this->htmlTemplate ($message, $status, $info);
    $body->write (ob_get_clean ());
  }
  else if (HttpUtil::clientAccepts ($request, 'application/json')) {
    $response = $response->withHeader ('Content-Type', 'application/json');
    $body->write (json_encode (['error' => ['code' => $status, 'message' => $message]]));
  }
  else if (HttpUtil::clientAccepts ($request, 'application/xml')) {
    $response = $response->withHeader ('Content-Type', 'application/xml');
    $body->write ("<?xml version=\"1.0\"?><error><code>$status</code><message>$message</message></error>");
  }
  else if (HttpUtil::clientAccepts ($request, 'text/plain') || HttpUtil::clientAccepts ($request, '*/*')) {
    $response = $response->withHeader ('Content-Type', 'text/plain');
    $body->write ($message);
  }
  // else render nothing

  return $response;
}

/**
 * Override this to render your customized template.
 * @param string $message The main message (single, unformatted line).
 * @param int    $status  If not zero, it will be displayed as an HTTP status code.
 * @param string $info    Additional HTML to display.
 */
protected function htmlTemplate ($message, $status, $info)
{
?><!DOCTYPE html>
<html>
  <head>
    <title><?= $message ?></title>
    <style>
      body {
        font-family: Helvetica, Arial, sans-serif;
        background: #eee;
      }

      kbd {
        color: #000;
        font-weight: 100;
        font-size: 15px;
        font-family: monospace;
      }

      h1 {
        clear: both;
        font-weight: 300;
        padding: 30px;
        font-size: 24px;
      }

      h5 {
        font-size: 14px;
        font-weight: 300;
        margin: 0;
        color: #ddd;
        text-align: center;
      }

      p {
        margin: 0;
        padding: 16px 0;
      }

      .container {
        text-align: center;
      }

      .panel {
        color: #777;
        max-width: 600px;
        min-width: 400px;
        margin: 50px auto;
        padding: 0 0 5px;
        background: #FFF;
        border: 1px solid #CCC;
        display: inline-block;
        border-radius: 5px;;
      }

      header {
        background: #555;
        padding: 10px 30px;
        border-radius: 5px 5px 0 0;;
      }

      header::after {
        content: '';
        clear: both;
        display: block;
      }

      article {
        padding: 15px;
      }
    </style>
  </head>
  <body>
    <div class='container'>
      <div class='panel'>
        <?php
        if ($status): ?>
          <header>
            <h5 style='float:left'>HTTP <?= $status ?> &nbsp;</h5>
            <h5 style='float:right'>&nbsp; <?= $this->app->appName ?></h5>
          </header>
          <article>
            <h1><?= $message ?></h1>
            <p align=center><?= $info ?></p>
          </article>
          <?php

        else: ?>
          <header>
            <h5><?= $this->app->appName ?></h5>
          </header>
          <article>
            <h1 style='clear:both' align=center><?= $message ?></h1>
            <p align=center><?= $info ?></p>
          </article>
        <?php endif ?>
      </div>
    </div>
  </body>
</html>
<?php
}

}
