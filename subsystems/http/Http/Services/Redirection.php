<?php
namespace Selenia\Http\Services;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Selenia\Application;
use Selenia\Interfaces\RedirectionInterface;
use Selenia\Interfaces\ResponseFactoryInterface;
use Selenia\Interfaces\SessionInterface;
use Zend\Diactoros\Response;

class Redirection implements RedirectionInterface
{
  /**
   * @var Application
   */
  private $app;
  /**
   * @var ServerRequestInterface
   */
  private $request;
  /**
   * @var ResponseFactoryInterface
   */
  private $responseFactory;
  /**
   * @var SessionInterface
   */
  private $session;

  /**
   * @param ServerRequestInterface   $request         The current request.
   * @param ResponseFactoryInterface $responseFactory A factory fpr creating new responses.
   * @param Application              $app
   * @param SessionInterface         $session         The current session (always available, even if session support is
   *                                                  disabled).
   */
  function __construct (ServerRequestInterface $request, ResponseFactoryInterface $responseFactory, Application $app,
                        SessionInterface $session)
  {
    $this->request         = $request;
    $this->responseFactory = $responseFactory;
    $this->app             = $app;
    $this->session         = $session;
  }

  function back ($status = 302)
  {
    return $this->to ($this->request->getHeaderLine ('Referer') ?: $this->request->getUri (), $status);
  }

  function guest ($url, $status = 302)
  {
    $this->session->setPreviousUrl ($this->request->getUri ());
    $url = $this->normalizeUrl ($url);
    return $this->responseFactory->make ($status, '', '', ['Location' => $url]);
  }

  function home ($status = 302)
  {
    return $this->to ($this->app->baseURI, $status);
  }

  function intended ($defaultUrl = '', $status = 302)
  {
    $url = $this->session->previousUrl () ?: $this->normalizeUrl ($defaultUrl);
    return $this->to ($url, $status);
  }

  function refresh ($status = 302)
  {
    return $this->to ($this->request->getUri (), $status);
  }

  function secure ($url, $status = 302)
  {
    $url = str_replace ('http://', 'https://', $this->normalizeUrl ($url));
    return $this->responseFactory->make ($status, '', '', ['Location' => $url]);
  }

  function to ($url, $status = 302)
  {
    $url = $this->normalizeUrl ($url);
    return $this->responseFactory->make ($status, '', '', ['Location' => $url]);
  }

  /**
   * Converts the given URL to an absolute URL and returns it as a string.
   * @param string|UriInterface $url
   * @return string
   */
  protected function normalizeUrl ($url)
  {
    $url = strval ($url);
    if (!$url)
      return strval ($this->request->getUri ());
    if ($url[0] != '/' && substr ($url, 0, 4) != 'http')
      $url = $this->app->baseURI . "/$url";
    return $url;
  }

}
