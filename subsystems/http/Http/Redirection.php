<?php
namespace Selenia\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Selenia\Application;
use Selenia\Interfaces\ResponseFactoryInterface;
use Selenia\Interfaces\SessionInterface;
use Zend\Diactoros\Response;

class Redirection
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

  /**
   * Creates a new redirection response to the previous location.
   * <p>If the previous location is not know, it redirects to the current URL.
   * @param int $status
   * @return \Psr\Http\Message\ResponseInterface
   */
  function back ($status = 302)
  {
    return $this->to ($this->request->getHeaderLine ('Referer') ?: $this->request->getUri (), $status);
  }

  /**
   * Creates a new redirection response, while saving the current URL in the session.
   * @param string|UriInterface $url    A relative or an absolute URL. If empty, it is equivalent to the current URL.
   * @param int                 $status HTTP status code.
   * @return \Psr\Http\Message\ResponseInterface
   */
  function guest ($url, $status = 302)
  {
    $this->session->setPreviousUrl ($this->request->getUri ());
    $url = $this->normalizeUrl ($url);
    return $this->responseFactory->make ($status, '', '', ['Location' => $url]);
  }

  /**
   * Creates a new redirection response to the application's root URL.
   * @param int $status
   * @return \Psr\Http\Message\ResponseInterface
   */
  function home ($status = 302)
  {
    return $this->to ($this->app->baseURI, $status);
  }

  /**
   * Creates a new redirection response to the previously intended location, which is the one set previously by calling
   * `guest()`.
   * @param string|UriInterface $defaultUrl A relative or an absolute URL to be used when a saved URL is not found on
   *                                        the session. If empty, it is equivalent to the current URL.
   * @param int                 $status     HTTP status code.
   * @return \Psr\Http\Message\ResponseInterface
   */
  function intended ($defaultUrl = '', $status = 302)
  {
    $url = $this->session->previousUrl () ?: $this->normalizeUrl ($defaultUrl);
    return $this->to ($url . $status);
  }

  /**
   * Creates a new redirection response to the current URL.
   * @param int $status
   * @return \Psr\Http\Message\ResponseInterface
   */
  function refresh ($status = 302)
  {
    return $this->to ($this->request->getUri (), $status);
  }

  /**
   * Creates a new redirection response to the given secure (https) URL.
   * @param string|UriInterface $url    A relative or an absolute URL. If empty, it is equivalent to the current URL.
   *                                    The protocol part is always replaced by 'https'.
   * @param int                 $status HTTP status code.
   * @return \Psr\Http\Message\ResponseInterface
   */
  function secure ($url, $status = 302)
  {
    $url = str_replace ('http://', 'https://', $this->normalizeUrl ($url));
    return $this->responseFactory->make ($status, '', '', ['Location' => $url]);
  }

  /**
   * Creates a new redirection response to the given URL.
   * @param string|UriInterface $url    A relative or an absolute URL. If empty, it is equivalent to the current URL.
   * @param int                 $status HTTP status code.
   * @return \Psr\Http\Message\ResponseInterface
   */
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
    if ($url[0] != '/')
      $url = $this->app->baseURI . "/$url";
    return $url;
  }

}
