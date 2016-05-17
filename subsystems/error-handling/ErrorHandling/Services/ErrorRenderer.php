<?php
namespace Selenia\ErrorHandling\Services;

use PhpKit\WebConsole\ErrorConsole\ErrorConsole;use Psr\Http\Message\ResponseInterface;use Psr\Http\Message\ServerRequestInterface;use Selenia\Application;use Selenia\ErrorHandling\Config\ErrorHandlingSettings;use Selenia\Exceptions\HttpException;use Selenia\Http\Lib\Http;use Selenia\Interfaces\DI\InjectorInterface;use Selenia\Interfaces\Http\ErrorRendererInterface;use Selenia\Interfaces\Http\ResponseFactoryInterface;use Selenia\Interfaces\RenderableInterface;

/**
 * Renders an error HTTP response into a format supported by the client.
 */
class ErrorRenderer implements ErrorRendererInterface
{
protected $app;/**
 * @var bool*/
private $debugMode;
/** @var InjectorInterface It is required for instantiating custom error renderers. */
private $injector;
private $responseFactory;
private $settings;

/**
 * ErrorRenderer constructor.
 *
 * @param Application              $app
 * @param ResponseFactoryInterface $responseFactory
 * @param ErrorHandlingSettings    $settings
 * @param InjectorInterface        $injector
 * @param bool   $debugMode
 */function __construct (Application $app, ResponseFactoryInterface $responseFactory, ErrorHandlingSettings $settings,
                         InjectorInterface $injector, $debugMode)
{
  $this->app             = $app;
  $this->responseFactory = $responseFactory;
  $this->settings        = $settings;
  $this->injector        = $injector;
  $this->debugMode = $debugMode;
}

function render (ServerRequestInterface $request, ResponseInterface $response, $error = null)
{
  if ($error) {

    // On debug mode, a debugging error popup is displayed for Exceptions/Errors.

    if ($this->debugMode && Http::clientAccepts ($request, 'text/html'))
      return ErrorConsole::display ($error, $this->responseFactory->makeHtmlResponse ());

    $status = $error instanceof HttpException ? $error->getCode () : 500;
    // Errors may contain an additional `getTitle()` method.
    if (method_exists ($error, 'getTitle')) {
      // The title is assumed to be a plain, one-line string (no formatting). If not, make it so.
      $title   = strip_tags ($error->getTitle ());
      $message = $error->getMessage ();
    }
    else {
      $title   = strip_tags ($error->getMessage ());
      $message = '';
    }
    $response = $response->withStatus ($status);
  }
  else {
    $status  = $response->getStatusCode ();
    $title   = $response->getReasonPhrase ();
    $message = strval ($response->getBody ());
  }
  /** @var ResponseInterface $response */
  $response = $response->withBody ($body = $this->responseFactory->makeBody ());

  // Otherwise, errors are rendered into a format accepted by the HTML client.

  if (Http::clientAccepts ($request, 'text/html')) {
    $response       = $response->withHeader ('Content-Type', 'text/html');
    $customRenderer = $this->settings->getCustomRenderer ($status);

    if ($customRenderer) {
      if ($customRenderer instanceof RenderableInterface) {
        $class = $customRenderer->getContextClass ();
        $customRenderer->setContext ($this->injector->make ($class));
      }
      $response = $customRenderer($request, $response, nop ());
    }
    else {
      ob_start ();
      $this->htmlTemplate ($status, $title, $message);
      $body->write (ob_get_clean ());
    }
  }
  elseif (Http::clientAccepts ($request, 'application/json')) {
    $response = $response->withHeader ('Content-Type', 'application/json');
    $body->write (json_encode (['error' => ['code' => $status, 'message' => $title]]));
  }
  elseif (Http::clientAccepts ($request, 'application/xml')) {
    $response = $response->withHeader ('Content-Type', 'application/xml');
    $body->write ("<?xml version=\"1.0\"?><error><code>$status</code><message>$title</message></error>");
  }
  elseif (Http::clientAccepts ($request, 'text/plain') || Http::clientAccepts ($request, '*/*')) {
    $response = $response->withHeader ('Content-Type', 'text/plain');
    $body->write ($title);
  }

  // else render nothing
  return $response;
}

/**
 * Override this to render your customized template.
 *
 * @param int    $status  If not zero, it will be displayed as an HTTP status code.
 * @param string $title   The main message (single, unformatted line).
 * @param string $message Additional HTML to display.
 */
protected function htmlTemplate ($status, $title, $message)
{
?><!DOCTYPE html>
<html>
  <head>
    <title><?= $title ?></title>
    <style>
      body {
        font-family: Helvetica, Arial, sans-serif;
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        box-sizing: border-box;
        background: #D5D5D5;
        margin: 30px 40px 60px 40px;
      }

      kbd {
        color: #000;
        font-weight: 100;
        font-size: 15px;
        font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
      }

      h1 {
        font-weight: 300;
        margin: 0;
        padding-bottom: 30px;
        font-size: 28px;
      }

      h5 {
        font-size: 14px;
        font-weight: 300;
        margin: 0;
        color: #BBB;
        text-align: center;
        padding: 10px;
      }

      p {
        margin: 0;
      }

      body > div {
        display: table-row;
      }

      .container {
        background: #FFF;
        display: table;
        width: 100%;
        height: 100%;
        max-height: 400px;
        max-width: 600px;
        color: #777;
        text-align: center;
        box-shadow: 1px 1px 5px 0 rgba(0, 0, 0, 0.3);
        border-radius: 5px;
        margin: auto;
      }

      .row1, .row2 {
        display: table-row;
      }

      header {
        display: table-cell;
        padding: 10px 15px;
      }

      header::after {
        content: '';
        clear: both;
        display: block;
      }

      article {
        display: table-cell;
        vertical-align: middle;
        height: 100%;
        padding-bottom: 56px;
      }
    </style>
  </head>
  <body>
    <div class=container>
      <div class=row1>
        <header>
          <h5 style='float:left'>HTTP <?= $status ?> &nbsp;</h5>
          <h5 style='float:right'>&nbsp; <?= $this->app->appName ?></h5>
        </header>
      </div>
      <div class=row2>
        <article>
          <h1><?= $title ?></h1>
          <p align=center><?= $message ?></p>
        </article>
      </div>
    </div>
  </body>
</html>
<?php
}

}
