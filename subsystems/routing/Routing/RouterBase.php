<?php
namespace Selenia\Routing;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouterBase
{
  /**
   * Parses and executes a routable reference.
   * @param callable|string        $routable
   * @param string  $tail
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response
   * @param callable               $next
   * @return false|ResponseInterface
   */
  protected function exec ($routable, $tail, ServerRequestInterface $request, ResponseInterface $response, callable $next)
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
   * @param string $methods
   * @return bool
   */
  protected function matchesMethods ($methods)
  {
    return $methods == '*' || in_array ($this->request->getMethod (), explode ('|', $methods));
  }

}
