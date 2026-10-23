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

  function match($pattern, ServerRequestInterface $request)
  {
    $path            = $request->getRequestTarget();
    if ($path == '.') $path = '';

    if (!preg_match(self::SYNTAX, $pattern, $m))
      throw new ConfigException(sprintf("Invalid route pattern matching expression: '<kbd>%s</kbd>'", $pattern));

    array_push($m, '', '');
    list($all, $methods, $pathPattern) = $m;

    if ($methods && !in_array($request->getMethod(), explode('|', rtrim($methods))))
      return false;

    // The dot matches an empty path.
    if ($pathPattern == '.')
      return !strlen($path) ? $request : false;

    if ($path == '[empty-segment]') // remove marker
    {
      $path = '';
      $request = $this->injectRequestTarget($request, '');
    }

    // The asterisk matches any path.
    if ($pathPattern == '*')
      return $request;

    // The plus matches any non-empty path.
    if ($pathPattern == '+')
      return strlen($path) ? $request : false;

    // @parameters never match the empty path (which is encoded as a single dot)
    $compiledPattern = preg_replace(
      ['/(?<=[^\*\.])$/', '/\*$/', '/\.\.\.$/', '/@(\w+)/', '[\[\]\{\}\(\)\.\?]'],
      ['(?<_next>$)', '(?<_next>.*)', '(?=\/|$)(?<_next>)', '(?<$1>(?=(?:$|[^\.]))[^/]*)', '\\$0'],
      $pathPattern
    );

    if (!preg_match("#^$compiledPattern#", $path, $m2, PREG_OFFSET_CAPTURE))
      return false;

    $p = $m2['_next'][1];
    $newPath = substr($path, $p, 2) == '/' ? '[empty-segment]' : substr($path, $p + 1);
    if ($newPath === false)
      $newPath = '';
    if ($path != $newPath)
      $request = $this->injectRequestTarget($request, $newPath);

    foreach ($m2 as $k => $v)
      if (is_string($k) && $k[0] != '_') // exclude reserved _next key
        $request = $request->withAttribute('@' . $k, urldecode($v[0]));

    return $request;
  }

  private function injectRequestTarget(ServerRequestInterface $request, $newPath)
  {
    // should be "$request->withRequestTarget($newPath)" but now it throws if empty, but we need it empty for the router

    $new = clone $request;
    $refObject   = new \ReflectionObject($new);
    //get the inheritance chain
    $parents = [];
    while ($parent = $refObject->getParentClass())
    {
      $refObject = $parents[] = $parent;
    }
    foreach ($parents as $parent)
    {
      if ($parent->hasProperty('requestTarget'))
      {
        $refProperty = $parent->getProperty('requestTarget');
        // $refProperty->setAccessible(true);
        $refProperty->setValue($new, $newPath);
      }
    }
    return $new;
  }
}
