<?php
namespace Electro\Http\Services;

use Electro\Http\Lib\Http;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\Http\ResponseFactoryInterface;
use Electro\Interfaces\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * **Note:** this class assumes a `baseURI` attribute exists on the given ServerRequestInterface instance.
 */
class Redirection implements RedirectionInterface
{
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
   * @param ResponseFactoryInterface $responseFactory A factory fpr creating new responses.
   * @param SessionInterface         $session         The current session (always available, even if session support is
   *                                                  disabled).
   */
  function __construct (ResponseFactoryInterface $responseFactory, SessionInterface $session)
  {
    $this->responseFactory = $responseFactory;
    $this->session         = $session;
  }

  function back ($status = 302)
  {
    $this->validate ();
    return $this->to ($this->request->getHeaderLine ('Referer') ?: $this->request->getAttribute ('baseUri'), $status);
  }

  function guest ($url, $status = 302)
  {
    $this->validate ();
    $this->session->setPreviousUrl ($this->request->getUri ());
    $url = $this->normalizeUrl ($url);
    return $this->responseFactory->make ($status, '', '', ['Location' => $url]);
  }

  function home ($status = 302)
  {
    $this->validate ();
    return $this->to ($this->request->getAttribute ('baseUri'), $status);
  }

  function intended ($defaultUrl = '', $status = 302)
  {
    $this->validate ();
    $url = $this->session->previousUrl () ?: $this->normalizeUrl ($defaultUrl);
    return $this->to ($url, $status);
  }

  function refresh ($status = 302)
  {
    $this->validate ();
    return $this->to ($this->request->getUri (), $status);
  }

  function secure ($url, $status = 302)
  {
    $this->validate ();
    $url = str_replace ('http://', 'https://', $this->normalizeUrl ($url));
    return $this->responseFactory->make ($status, '', '', ['Location' => $url]);
  }

  function setRequest (ServerRequestInterface $request)
  {
    $this->request = $request;
    return $this;
  }

  function to ($url, $status = 302)
  {
    $this->validate ();
    $url = $this->normalizeUrl ($url);
    return $this->responseFactory->make ($status, '', '', ['Location' => $url]);
  }

  function toUrlWithParams($url, array $parameters, $status = 302)
  {
    return $this->to ("$url?" . http_build_query ($parameters), $status);
  }

  /**
   * Converts the given URL to an absolute URL and returns it as a string.
   *
   * @param string|UriInterface $url
   * @return string
   */
  protected function normalizeUrl ($url)
  {
    return Http::absoluteUrlOf ((string)$url, $this->request);
  }

  protected function validate ()
  {
    if (!$this->request)
      throw new \BadMethodCallException ("No <kbd class=type>ServerRequestInterface</kbd> instance was set on the <kbd class=type>Redirection</kbd> instance.");
  }

}
