<?php
namespace Electro\Core\WebApplication;

use Electro\Application;
use Electro\Exceptions\Fatal\ConfigException;
use Electro\Interfaces\Http\MiddlewareStackInterface;
use Electro\Interfaces\Http\ResponseSenderInterface;
use Electro\Interfaces\Http\Shared\ApplicationMiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
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
   * @var MiddlewareStackInterface
   */
  private $middlewareStack;
  /**
   * @var ResponseSenderInterface
   */
  private $responseSender;

  /**
   * @param Application                    $app
   * @param ApplicationMiddlewareInterface $middlewareStack
   * @param ResponseSenderInterface        $responseSender
   */
  function __construct (Application $app, ApplicationMiddlewareInterface $middlewareStack,
                        ResponseSenderInterface $responseSender)
  {
    $this->app             = $app;
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
   *
   * @param int $urlDepth How many URL segments should be stripped when calculating the application's root URL.
   */
  function setup ($urlDepth = 0)
  {
    $app          = $this->app;
    /** @var ServerRequestInterface $request */
    $request      = ServerRequestFactory::fromGlobals ();
    $app->baseURI = dirnameEx (get ($request->getServerParams (), 'SCRIPT_NAME'), $urlDepth + 1);
    $request       = $request->withAttribute ('originalUri', $request->getUri ());
    $request       = $request->withAttribute ('baseUri', $app->baseURI);
    $this->request = $request->withAttribute ('virtualUri', $this->getVirtualUri ($request));
  }

  private function getBaseUri (ServerRequestInterface $request)
  {
    /*
        $params = $request->getServerParams ();
        $sUrl   = dirnameEx (get ($params, 'SCRIPT_NAME'));
        $sUrl   = str_replace ('\\', '/', $sUrl); // Windows compat.

        if (strlen ($sUrl) != '/')// make sure that paths start and end with /
        {
          if (substr ($sUrl, 0, 1) != '/')
            $sUrl = '/' . $sUrl;
          if (substr ($sUrl, -1) != '/')
            $sUrl = $sUrl . '/';
        }
        return $sUrl;
    */
  }

  private function getVirtualUri (ServerRequestInterface $request)
  {
    $uri     = $request->getUri ()->getPath ();
    $baseURI = $request->getAttribute ('baseUri');
    $vuri    = substr ($uri, strlen ($baseURI) + 1) ?: '';
    return $vuri;
  }

}
