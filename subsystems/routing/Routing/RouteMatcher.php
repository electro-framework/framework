<?php
namespace Selenia\Routing;
use Selenia\Exceptions\FatalException;
use Selenia\Interfaces\RouteMatcherInterface;

/**
 * Implements Selenia's standard flavour of the the DSL route pattern matching syntax.
 */
class RouteMatcher implements RouteMatcherInterface
{
  const SYNTAX = '/^ ([a-z\|]:\s*)? (( \* | @\w+ | [\w\-] | \/ ) (?= $ | \/))* $/ix';

  function match ($pattern, $path, &$newPath)
  {
    if (!preg_match (self::SYNTAX, $pattern, $m))
      throw new FatalException (sprintf ("Invalid route pattern matching expression: <kbd>%s</kbd>", $pattern));

    list ($all, $methods, $pathPattern) = $m;

    if (!$this->matchesMethods (trim ($methods, ' :')))
      return false;

    // If $pathPattern is empty, it matches only if $path is also empty.
    if (!$pathPattern)
      return $path === '';

    if ($pathPattern == '*')
      return $path === '';

    $pathPattern = preg_replace ('/@(\w+)/', '(?P<$1>[^/]*)', $pathPattern);

    $match = preg_match ("#^$pathPattern(?:/|$)#", $path, $m, PREG_OFFSET_CAPTURE);

    //TODO
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

