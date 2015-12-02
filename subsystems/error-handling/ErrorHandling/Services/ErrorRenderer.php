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
  private   $responseFactory;

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

    $status  = $error->getCode ();
    if ($app->debugMode && HttpUtil::clientAccepts ($request, 'text/html')
    && (!$error instanceof HttpException || $status != 404 ))
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
      ob_start();
      $this->htmlTemplate ($message, $status, $info, $error);
      $body->write (ob_get_clean());
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
 * @param string $message
 * @param int $status
 * @param string $info
 * @param \Throwable|\Exception $error
 */
protected function htmlTemplate ($message, $status, $info, $error)
{
?><!DOCTYPE html>
<html>
  <head>
    <title><?= $message ?></title>
    <style>
      body {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        font-weight: 100;
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
        padding: 30px 0 0;
        color: #d44;
        font-size: 24px;
      }

      h1 + p { margin-top: 50px }

      h5 {
        font-weight: 100;
      }

      .container {
        text-align: center;
      }

      .panel {
        color: #777;
        max-width: 400px;
        margin: 50px auto;
        padding: 0px 30px 5px;
        background: #FFF;
        border: 1px solid #DDD;
        display: inline-block;
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
        if ($error instanceof HttpException): ?>
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
