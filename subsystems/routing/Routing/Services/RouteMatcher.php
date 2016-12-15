<?php
namespace Electro\Routing\Services;

use Electro\Exceptions\Fatal\ConfigException;
use Electro\Interfaces\Http\RouteMatcherInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Implements Electro's standard flavour of the the DSL route pattern matching syntax.
 */
class RouteMatcher implements RouteMatcherInterface
{
  const SYNTAX = '/^ ([A-Z\|]+\s)? ( \. | \* | \+ | (?: [\w\-\/@]+) (?:\*|\.\.\.)? ) $/x';

  function match ($pattern, ServerRequestInterface $request, ServerRequestInterface &$modifiedRequest)
  {
    $modifiedRequest = $request;
    $path            = $request->getRequestTarget ();
    if ($path == '.') $path = '';

    if (!preg_match (self::SYNTAX, $pattern, $m))
      throw new ConfigException (sprintf ("Invalid route pattern matching expression: '<kbd>%s</kbd>'", $pattern));

    array_push ($m, '', '');
    list ($all, $methods, $pathPattern) = $m;

    if ($methods && !in_array ($request->getMethod (), explode ('|', rtrim ($methods))))
      return false;

    // The asterisk matches any path.
    if ($pathPattern == '*')
      return true;

    // The plus matches any non-empty path.
    if ($pathPattern == '+')
      return !!strlen ($path);

    // @parameters never match the empty path (which is encoded as a single dot)
    $compiledPattern = preg_replace (
      ['/(?<=[^\*\.])$/', '/\*$/', '/\.\.\.$/', '/\.$/', '/@(\w+)/', '[\[\]\{\}\(\)\.\?]'],
      ['(?<_next>$)', '(?<_next>.*)', '(?<_next>)', '(?<_next>$)', '(?<$1>(?=(?:$|[^\.]))[^/]*)', '\\$0'],
      $pathPattern);

    if (!preg_match ("#^$compiledPattern#", $path, $m2, PREG_OFFSET_CAPTURE))
      return false;

    $newPath = substr ($path, $m2['_next'][1] + 1);
    if ($path != $newPath)
      $request = $request->withRequestTarget ($newPath === false ? '' : $newPath);

    foreach ($m2 as $k => $v)
      if (is_string ($k) && $k[0] != '_') // exclude reserved _next key
        $request = $request->withAttribute ('@' . $k, urldecode ($v[0]));
    $modifiedRequest = $request;
    return true;
  }

}

