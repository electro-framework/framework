<?php
namespace Selenia\Routing;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Exceptions\FatalException;
use Selenia\Interfaces\RouteMatcherInterface;

/**
 * Implements Selenia's standard flavour of the the DSL route pattern matching syntax.
 */
class RouteMatcher implements RouteMatcherInterface
{
  const SYNTAX = '/^ ([a-z\|]:\s*)? (( \* | @\w+ | [\w\-] | \/ ) (?= $ | \/))* $/ix';

  function match ($pattern, ServerRequestInterface &$request)
  {
    $path = $request->getRequestTarget ();

    if (!preg_match (self::SYNTAX, $pattern, $m))
      throw new FatalException (sprintf ("Invalid route pattern matching expression: <kbd>%s</kbd>", $pattern));

    list ($all, $methods, $pathPattern) = $m;

    if (!$this->matchesMethods (rtrim ($methods, ' :')))
      return false;

    // If $pathPattern is empty, it matches only if $path is also empty.
    if (!$pathPattern)
      return $path === '';

    // The asterisk matches any path.
    if ($pathPattern == '*')
      return true;

    $compiledPattern = preg_replace ('/@(\w+)/', '(?P<$1>[^/]*)', $pathPattern);

    $match = preg_match ("#^$compiledPattern(?:/|$)#", $path, $m, PREG_OFFSET_CAPTURE);

    _log($match);
    return false;
  }

  /**
   * @param string $methods
   * @return bool
   */
  private function matchesMethods ($methods)
  {
    return !$methods || in_array ($this->request->getMethod (), explode ('|', $methods));
  }

}

