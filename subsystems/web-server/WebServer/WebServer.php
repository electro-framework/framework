<?php

namespace Electro\WebServer;

use Electro\Exceptions\Fatal\ConfigException;
use Electro\Http\Lib\Response;
use Electro\Http\Lib\ServerRequest;
use Electro\Interfaces\Http\MiddlewareStackInterface;
use Electro\Interfaces\Http\ResponseSenderInterface;
use Electro\Interfaces\Http\Shared\ApplicationMiddlewareInterface;
use Electro\Kernel\Config\KernelSettings;
use PhpKit\WebConsole\ErrorConsole\ErrorConsole;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Responds to an HTTP request made to the web application by forwarding it to a PSR-7 compliant HTTP processing
 * pipeline and then it sends the generated response back to the HTTP client.
 */
class WebServer
{
  /**
   * @var ServerRequestInterface
   */
  public $request;
  /**
   * @var KernelSettings
   */
  private $kernelSettings;
  /**
   * @var MiddlewareStackInterface
   */
  private $middlewareStack;
  /**
   * @var ResponseSenderInterface
   */
  private $responseSender;

  /**
   * @param KernelSettings                 $kernelSettings
   * @param ApplicationMiddlewareInterface $middlewareStack
   * @param ResponseSenderInterface        $responseSender
   */
  function __construct (KernelSettings $kernelSettings, ApplicationMiddlewareInterface $middlewareStack,
                        ResponseSenderInterface $responseSender)
  {
    $this->kernelSettings  = $kernelSettings;
    $this->middlewareStack = $middlewareStack;
    $this->responseSender  = $responseSender;
  }

  /**
   * Runs the web server to handle the incoming HTTP request.
   */
  function run ()
  {
    $response   = new Response;
    $middleware = $this->middlewareStack;
    $response   = $middleware ($this->request, $response, function () {
      throw new ConfigException ("The HTTP request was not handled by any request handler. This should not happen.
<p>Please check the application's middleware configuration.");
    });
    if (!$response) return;

    // Send back the response.

    $this->responseSender->send ($response);
  }

  /**
   * Initializes the web server and sets up the request object.
   */
  function setup ()
  {
    /** @var ServerRequestInterface $request */
    $request        = ServerRequest::fromGlobals ();
    $uri            = $request->getUri ();
    $scriptName     = $request->getServerParams () ['SCRIPT_NAME'];
    $realBasePath   = dirname ($scriptName);
    $basePath       = dirnameEx ($scriptName, $this->kernelSettings->urlDepth + 1);
    $scheme         = $uri->getScheme () ?: 'http';
    $port           = $uri->getPort ();
    $baseUrl        = sprintf ('%s://%s%s%s', $scheme, $uri->getHost (),
      $port ? ($port == 80 && $scheme == 'http' || $port == 443 && $scheme == 'https' ? '' : ":$port") : '',
      $basePath);
    $path           = $uri->getPath ();
    $virtualUri     = ltrim (substr ($path, strlen ($basePath)), '/');
    $realVirtualUri = substr ($path, strlen ($realBasePath));
    // Strip trailing slash from virtual URI if it matches a real URI (ex: for sub-applications that have their own index.php on a subfolder)
    if (substr ($realVirtualUri, -1) == '/')
      $virtualUri = rtrim ($virtualUri, '/');
    $query = $uri->getQuery ();

    $this->kernelSettings->baseUrl  = $baseUrl;
    $this->kernelSettings->basePath = $basePath;

    ErrorConsole::setEditorUrl (($basePath ? "$basePath/" : '') . $this->kernelSettings->editorUrl);

    $request       = $request->withAttribute ('originalUri', "$baseUrl/$virtualUri" . ($query ? "?$query" : ''));
    $request       = $request->withAttribute ('baseUri', $basePath);
    $request       = $request->withAttribute ('baseUrl', $baseUrl);
    $this->request = $request->withAttribute ('virtualUri', $virtualUri);
  }

}
