<?php
namespace Electro\ErrorHandling\Services;

use Electro\ErrorHandling\Config\ErrorHandlingSettings;use Electro\Exceptions\HttpException;use Electro\Http\Lib\Http;use Electro\Interfaces\DI\InjectorInterface;use Electro\Interfaces\Http\ErrorRendererInterface;use Electro\Interfaces\Http\ResponseFactoryInterface;use Electro\Interfaces\RenderableInterface;use Electro\Kernel\Config\KernelSettings;use PhpKit\WebConsole\ErrorConsole\ErrorConsole;use Psr\Http\Message\ResponseInterface;use Psr\Http\Message\ServerRequestInterface;

/**
 * Renders an error HTTP response into a format supported by the client.
 */
class ErrorRenderer implements ErrorRendererInterface
{
protected $kernelSettings;/**
 * @var bool
 */
private $devEnv;
/** @var InjectorInterface It is required for instantiating custom error renderers. */
private $injector;
private $responseFactory;
private $settings;

/**
 * ErrorRenderer constructor.
 *
 * @param KernelSettings           $kernelSettings
 * @param ResponseFactoryInterface $responseFactory
 * @param ErrorHandlingSettings    $settings
 * @param InjectorInterface        $injector
 * @param bool                     $devEnv
 */function __construct (KernelSettings $kernelSettings, ResponseFactoryInterface $responseFactory,
                         ErrorHandlingSettings $settings, InjectorInterface $injector, $devEnv)
{
  $this->kernelSettings  = $kernelSettings;
  $this->responseFactory = $responseFactory;
  $this->settings        = $settings;
  $this->injector        = $injector;
  $this->devEnv          = $devEnv;
}

function render (ServerRequestInterface $request, ResponseInterface $response, $error = null)
{
  if ($error) {

    // On debug mode, a debugging error popup is displayed for Exceptions/Errors.

    if ($this->devEnv && Http::clientAccepts ($request, 'text/html'))
      return ErrorConsole::display ($error, $this->responseFactory->makeHtmlResponse ());

    $status = $error instanceof HttpException ? $error->getCode () : 500;
    // Errors may contain an additional `getTitle()` method.
    if (method_exists ($error, 'getTitle')) {
      // The title is assumed to be a plain, one-line string (no formatting). If not, make it so.
      $title   = $error->getTitle ();
      $message = $error->getMessage ();
    }
    else list ($title, $message) = array_pad (explode (PHP_EOL, $error->getMessage (), 2), 2, '');
    $response = $response->withStatus ($status);
  }
  else {
    $status  = $response->getStatusCode ();
    $title   = $response->getReasonPhrase ();
    $message = strval ($response->getBody ());
  }

  // Suppress the display of extra information when on production mode.
  if (!$this->devEnv)
    $message = '';

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
  else {
    $title = strip_tags ($title);
    $message = strip_tags ($message);

    if (Http::clientAccepts ($request, 'text/plain') || Http::clientAccepts ($request, '*/*')) {
      $response = $response->withHeader ('Content-Type', 'text/plain');
      $body->write ("$title
$message");
    }
    elseif (Http::clientAccepts ($request, 'application/json')) {
      $response = $response->withHeader ('Content-Type', 'application/json');
      $body->write (json_encode (['error' => ['code' => $status, 'message' => $title, 'info' => $message]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    elseif (Http::clientAccepts ($request, 'application/xml')) {
      $response = $response->withHeader ('Content-Type', 'application/xml');
      $body->write ("<?xml version=\"1.0\"?><error><code>$status</code><message>$title</message><info>$message</info></error>");
    }
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

      kbd, path {
        font-weight: bold;
        letter-spacing: -1px;
        font-size: smaller;
        font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
      }

      h1 {
        font-weight: 300;
        margin: 0 0 30px;
        font-size: 22px;
        text-align: center;
      }

      h5 {
        font-size: 14px;
        line-height: 15px;
        font-weight: 300;
        margin: 0;
        color: #DDD;
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
        min-height: 300px;
        max-height: 400px;
        max-width: 600px;
        color: #777;
        box-shadow: 1px 1px 5px 0 rgba(0, 0, 0, 0.3);
        border-radius: 5px;
        margin: auto;
      }

      .row1, .row2 {
        display: table-row;
      }

      header {
        display: table-cell;
        padding: 5px 30px;
        background: #617477;
        border-radius: 5px 5px 0 0;
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
        padding: 0 30px;
        text-align: center;
      }

      .message {
        display: inline-block;
        text-align: left;
        font-size: 16px;
      }
    </style>
  </head>
  <body>
    <div class=container>
      <div class=row1>
        <header>
          <h5 style='float:left'>HTTP <?= $status ?> &nbsp;</h5>
          <h5 style='float:right'>&nbsp; <?= $this->kernelSettings->appName ?></h5>
        </header>
      </div>
      <div class=row2>
        <article>
          <h1><?= $title ?></h1>
          <div class=message><?= $message ?></div>
        </article>
      </div>
    </div>
  </body>
</html>
<?php
}

}
