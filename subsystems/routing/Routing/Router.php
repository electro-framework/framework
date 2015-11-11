<?php
namespace Selenia\Routing;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Interfaces\RedirectionInterface;
use Selenia\Interfaces\RouteInterface;
use Selenia\Interfaces\RouterInterface;

/**
 * Routes the current URL to a matching request handler
 */
class Router implements RouterInterface
{
  /**
   * @var RedirectionInterface
   */
  private $redirection;
  /**
   * @var ServerRequestInterface
   */
  private $request;
  /**
   * @var ResponseInterface
   */
  private $response;
  /**
   * @var RouteInterface
   */
  private $route;

  /**
   * Router constructor.
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response
   * @param RouteInterface         $route
   * @param RedirectionInterface   $redirection
   */
  public function __construct (ServerRequestInterface $request, ResponseInterface $response, RouteInterface $route,
                               RedirectionInterface $redirection)
  {
    $this->route       = $route;
    $this->request     = $request;
    $this->response    = $response;
    $this->redirection = $redirection;
  }

  function dispatch (array $map)
  {
    $location = $this->route->location ();
    return isset($map[$location]) ? $map[$location] ($this) : false;
  }

  function match ($pattern, callable $routable)
  {
    list ($match, $param) = explode (':', "$pattern:");
    $location = '';
    // If $match is empty, performs wildcard matching.
    if ($match) {
      $location = $this->route->location ();
      // Regular expression matching.
      if ($match[0] == '/') {
        if (!preg_match ($match, $location, $m))
          return false;
        // Use the capture group value, if one is defined on the regexp.
        else if (count ($m) > 1)
          $location = $m[1];
      }
      // Literal matching.
      else if ($match != $location)
        return false;
    }
    // Save the route paramenter, if required.
    if ($param)
      $this->route->params () [$param] = $location;
    // The match succeeded, so call the routable.
    return $routable ($this);
  }

  function next (ResponseInterface $response = null)
  {
    return new static ($this->request, $response ?: $this->response, $this->route->next (), $this->redirection);
  }

  function on ($methods, callable $routable)
  {
    if ($methods != '*' && strpos ($methods, $this->request->getMethod ()) === false)
      return false;
    return $routable ($this);
  }

  function onTarget ($methods, callable $routable)
  {
    return $this->route->target () ? $this->on ($methods, $routable) : false;
  }

  function redirection ()
  {
    return $this->redirection;
  }

  function request ()
  {
    return $this->request;
  }

  function response ()
  {
    return $this->response;
  }

  function route ()
  {
    return $this->route;
  }
}

