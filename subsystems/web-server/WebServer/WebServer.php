<?php
namespace Electro\WebServer;

use Electro\Exceptions\Fatal\ConfigException;
use Electro\Interfaces\Http\MiddlewareStackInterface;
use Electro\Interfaces\Http\ResponseSenderInterface;
use Electro\Interfaces\Http\Shared\ApplicationMiddlewareInterface;
use Electro\Kernel\Config\KernelSettings;
use PhpKit\WebConsole\ErrorConsole\ErrorConsole;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

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
    $request  = ServerRequestFactory::fromGlobals ();
    $basePath = dirnameEx (
      get ($request->getServerParams (), 'SCRIPT_NAME'),
      $this->kernelSettings->urlDepth + 1
    );
    $uri      = $request->getUri ();

    $baseUrl                        = $this->kernelSettings->baseUrl = (string)$uri->withPath ($basePath);
    $this->kernelSettings->basePath = $basePath;

    ErrorConsole::setEditorUrl (($basePath ? "$basePath/" : '') . $this->kernelSettings->editorUrl);

    $request       = $request->withAttribute ('originalUri', (string)$uri);
    $request       = $request->withAttribute ('baseUri', $basePath);
    $request       = $request->withAttribute ('baseUrl', $baseUrl);
    $this->request = $request->withAttribute ('virtualUri', $this->getVirtualUri ($request));
  }

  protected function getVirtualUri (ServerRequestInterface $request)
  {
    $uri     = $request->getUri ()->getPath ();
    $baseUrl = $request->getAttribute ('baseUri');
    $vuri    = substr ($uri, strlen ($baseUrl) + 1) ?: '';
    return $vuri;
  }

}
