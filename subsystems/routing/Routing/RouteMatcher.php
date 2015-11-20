<?php
namespace Selenia\Routing;
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
    _log ("MATCHING PATTERN '$pattern' to PATH '$path'");

    if (!preg_match (self::SYNTAX, $pattern, $m))
      throw new ConfigException (sprintf ("Invalid route pattern matching expression: <kbd>%s</kbd>", $pattern));

    array_push ($m, '', '');
    list ($all, $methods, $pathPattern) = $m;
    _log ("PARSED", $m);

    if ($methods && !in_array ($request->getMethod (), explode ('|', rtrim ($methods, ' :'))))
      return false;

    _log ("Method matched");

    // If $pathPattern is empty, it matches only if $path is also empty.
    if (!$pathPattern) {
      _log (!strlen ($path) ? "MATCHED!" : "NOT MATCHED", "'$path'", strlen ($path));
      return !strlen ($path);
    }

    // The asterisk matches any path.
    if ($pathPattern == '*')
      return true;

    $compiledPattern = preg_replace ('/@(\w+)/', '(?<$1>[^/]*)', $pathPattern);

    if (!preg_match ("#^$compiledPattern(?<_next>/|$)#", $path, $m, PREG_OFFSET_CAPTURE))
      return false;

    _log ("PATH MATCH", $m);

    $newPath = substr ($path, $m['_next'][1] + 1);
    if ($path != $newPath)
      $request = $request->withRequestTarget ($newPath);

    foreach ($m as $k => $v)
      if (is_string ($k) && $k[0] != '_')
        $request = $request->withAttribute ('@' . $k, $v[0]);
    _log ("new path: $newPath, attrs:", $request->getAttributes ());
    $modifiedRequest = $request;
    return true;
  }

}

