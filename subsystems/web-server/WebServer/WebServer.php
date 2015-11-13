<?php
namespace Selenia\WebServer;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\FileServer\Services\FileServerMappings;
use Selenia\Interfaces\Http\MiddlewareStackInterface;
use Selenia\Interfaces\Http\ResponseSenderInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Responds to an HTTP request made to the web application by forwarding it to a PSR-7 compliant HTTP processing
 * pipeline and sends the generated response back to the HTTP client.
 */
class WebServer
{
  /**
   * @var ServerRequestInterface
   */
  public $request;
  /**
   * @var Application
   */
  private $app;
  /**
   * @var FileServerMappings
   */
  private $fileServerMappings;
  /**
   * @var MiddlewareStackInterface
   */
  private $middlewareStack;
  /**
   * @var ResponseSenderInterface
   */
  private $responseSender;

  /**
   * @param Application              $app
   * @param MiddlewareStackInterface $middlewareStack
   * @param ResponseSenderInterface  $responseSender
   * @param FileServerMappings       $fileServerMappings
   */
  function __construct (Application $app, MiddlewareStackInterface $middlewareStack,
                        ResponseSenderInterface $responseSender, FileServerMappings $fileServerMappings)
  {
    $this->app                = $app;
    $this->fileServerMappings = $fileServerMappings;
    $this->middlewareStack    = $middlewareStack;
    $this->responseSender     = $responseSender;
  }

  /**
   * Runs the web server to handle the incoming HTTP request.
   */
  function run ()
  {
    $response   = new Response;
    $middleware = $this->middlewareStack;
    $response   = $middleware ($this->request, $response, null);
    if (!$response) return;

    // Send back the response.

    $this->responseSender->send ($response);
  }

  /**
   * Initializes the web server and sets up the request object.
   */
  function setup ()
  {
    $app = $this->app;
    $this->fileServerMappings->map ($app->frameworkURI,
      $app->frameworkPath . DIRECTORY_SEPARATOR . $app->modulePublicPath);

    // Process the request.

    $request       = ServerRequestFactory::fromGlobals ();
    $app->baseURI  = $this->getBaseUri ($request);
    $request       = $request->withAttribute ('baseUri', $app->baseURI);
    $app->VURI     = $this->getVirtualUri ($request);
    $this->request = $request->withAttribute ('virtualUri', $app->VURI);
  }

  private function getBaseUri (ServerRequestInterface $request)
  {
    $params = $request->getServerParams ();
    return dirnameEx (get ($params, 'SCRIPT_NAME'));
  }

  private function getVirtualUri (ServerRequestInterface $request)
  {
    $uri     = $request->getUri ()->getPath ();
    $baseURI = $request->getAttribute ('baseUri');
    $vuri    = substr ($uri, strlen ($baseURI) + 1) ?: '';
    return $vuri;
  }

}
