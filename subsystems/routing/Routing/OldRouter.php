<?php
namespace Selenia\Routing;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Interfaces\Http\HandlerPipelineInterface;
use Selenia\Interfaces\Http\RedirectionInterface;
use Selenia\Interfaces\Http\RouteInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\InjectorInterface;

/**
 * Routes the current URL to a matching request handler
 */
class OldRouter implements RouterInterface
{
  /** @var Router This is used by the injector */
  static $current;
  /**
   * @var InjectorInterface
   */
  private $injector;
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
   * @param InjectorInterface      $injector
   */
  public function __construct (ServerRequestInterface $request, ResponseInterface $response, RouteInterface $route,
                               RedirectionInterface $redirection, InjectorInterface $injector)
  {
    $this->route       = $route;
    $this->request     = $request;
    $this->response    = $response;
    $this->redirection = $redirection;
    $this->injector    = $injector;
  }

  function dispatch (array $map)
  {
    $location = $this->route->location ();
    return isset($map[$location]) ? $this->exec ($map[$location]) : false;
  }

  function match ($methods, $pattern, callable $routable = null)
  {
    if (!$this->matchesMethods ($methods))
      return false;

    // If $match is empty, performs wildcard matching.
    if ($pattern) {
      $location = $this->route->location ();

      // If a parameter definition is specified.
      if ($pattern[0] == '{') {
        $pattern = substr ($pattern, 1, -1);;
        list ($param, $match) = explode (':', "$pattern:");
      }
      else {
        $param = '';
        $match = $pattern;
      }

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

      // Save the route paramenter, if required.
      if ($param)
        $this->route->params () [$param] = $location;
    }
    // The match succeeded, so call the routable or return `true` if none.
    return !$routable ?: $this->exec ($routable);
  }

  function matchPrefix ($path, $routable)
  {
    $path      = preg_quote ($path);
    $remaining = $this->route->remaining ();
    if (preg_match ("/^$path(?=\\/|$)/", $remaining)) {
      $this->exec ($routable,
        $this->make (new Route (substr ($remaining, strlen ($path) + 1), $this->route->prefix () . "/$path",
            $this->route->params ())
        )
      );
    }
    return false;
  }

  function middleware ($middleware)
  {
    if (isset ($middleware)) {
      if (is_array ($middleware)) {
        $stack = $this->injector->make (HandlerPipelineInterface::class);
        /** @var HandlerPipelineInterface $stack */
        $stack->add ($middleware);
        $middleware = $stack;
      }
      else if (!is_callable ($middleware)) {
        if (is_string ($middleware))
          $middleware = $this->injector->make ($middleware);
        else throw new \InvalidArgumentException ("Invalid middleware type: " . gettype ($middleware));
      }
      $response = $middleware ($this->request, $this->response,
        function (ServerRequestInterface $request = null, ResponseInterface $response = null) {
          if ($request) $this->request = $request;
          if ($response) $this->response = $response;
        }
      );
      if ($response) $this->response = $response;
    }
    return $this;
  }

  function next (ResponseInterface $response = null)
  {
    return $this->make ($this->route->next ());
  }

  function onTraverse ($methods, $routable)
  {
    return $this->matchesMethods ($methods) ? $this->exec ($routable) : false;
  }

  function on ($methods, $routable = null)
  {
    return $this->route->target () ? ($routable ? $this->onTraverse ($methods, $routable) : false) : false;
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

  /**
   * Parses and executes a routable reference.
   * @param callable|string $routable
   * @return ResponseInterface|false
   */
  private function exec ($routable)
  {
    self::$current = $this;
    if (!is_callable ($routable)) {
      if (class_exists ($routable))
        $routable = $this->injector->make ($routable);
      if (!is_callable ($routable))
        throw new \RuntimeException (sprintf ("Invalid routable reference: <kbd>%s</kbd>",
          var_export ($routable, true)));
    }
    $r = $this->injector->execute ($routable);
    if (!$r || $r instanceof ResponseInterface)
      return $r;
    if (is_callable ($r))
      return $this->injector->execute ($r);
    throw new \RuntimeException (sprintf ("Invalid return type from routable: <kbd>%s</kbd>",
      typeOf ($routable)));
  }

  /**
   * Clones the instance.
   * @param RouteInterface         $route
   * @param ResponseInterface|null $response
   * @return static
   */
  private function make (RouteInterface $route, ResponseInterface $response = null)
  {
    return new static ($this->request, $response ?: $this->response, $route, $this->redirection,
      $this->injector);
  }

  /**
   * @param string $methods
   * @return bool
   */
  private function matchesMethods ($methods)
  {
    return $methods == '*' || in_array ($this->request->getMethod (), explode ('|', $methods));
  }

}

