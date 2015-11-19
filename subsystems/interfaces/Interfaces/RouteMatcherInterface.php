<?php
namespace Selenia\Interfaces;

/**
 * Implements a specific flavour of the the DSL route pattern matching syntax.
 */
interface RouteMatcherInterface
{
  /**
   * Checks a given URL path against a route matching pattern to see if they match.
   *
   * @param string $pattern The route matching pattern. See the routing documentation for details about the DSL syntax.
   * @param string $path    An URL path. It should not begin with slash. If a single slash is desired, pass an empty
   *                        string.
   * @param string $newPath Returns the new path after the matched portion of the previous one is consumed.
   *                        It may equals the original path if the pattern matches the initial location.
   * @return bool true if the pattern matches the path.
   */
  function match ($pattern, $path, &$newPath);
}
