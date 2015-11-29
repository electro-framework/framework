<?php
namespace Selenia\Routing\Services;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Exceptions\Fatal\ConfigException;
use Selenia\Interfaces\Http\RouteMatcherInterface;

/**
 * Implements Selenia's standard flavour of the the DSL route pattern matching syntax.
 */
class RouteMatcher implements RouteMatcherInterface
{
  const SYNTAX = '/^ ([A-Z\|]+:\s*)? ( \* | (?: [\w\-@\/]* ) ) $/x';

  function match ($pattern, ServerRequestInterface $request, ServerRequestInterface &$modifiedRequest)
  {
    $modifiedRequest = $request;
    $path            = $request->getRequestTarget ();

    if (!preg_match (self::SYNTAX, $pattern, $m))
      throw new ConfigException (sprintf ("Invalid route pattern matching expression: <kbd>%s</kbd>", $pattern));

    array_push ($m, '', '');
    list ($all, $methods, $pathPattern) = $m;

    if ($methods && !in_array ($request->getMethod (), explode ('|', rtrim ($methods, ' :'))))
      return false;

    // If $pathPattern is empty, it matches only if $path is also empty.
    if (!$pathPattern)
      return !strlen ($path); // Note: the == or === operators are not used because they caused problems.

    // The asterisk matches any path.
    if ($pathPattern == '*')
      return true;

    $compiledPattern = preg_replace ('/@(\w+)/', '(?<$1>[^/]*)', $pathPattern);

    if (!preg_match ("#^$compiledPattern(?<_next>/|$)#", $path, $m, PREG_OFFSET_CAPTURE))
      return false;

    $newPath = substr ($path, $m['_next'][1] + 1);
    if ($path != $newPath)
      $request = $request->withRequestTarget ($newPath === false ? '' : $newPath);

    foreach ($m as $k => $v)
      if (is_string ($k) && $k[0] != '_')
        $request = $request->withAttribute ('@' . $k, $v[0]);
    $modifiedRequest = $request;
    return true;
  }

}

