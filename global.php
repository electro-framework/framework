<?php
use Electro\Exceptions\Fault;
use Electro\Faults\Faults;
use Electro\Http\Lib\Http;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
use Electro\Routing\Lib\FactoryRoutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Generates a routable that, when invoked, will return a generic PageComponent with the specified template as a view.
 *
 * <p>Use this to define routes for simple pages that have no controller logic.
 *
 * @param string $templateUrl
 * @return FactoryRoutable
 */
function page ($templateUrl)
{
  return new FactoryRoutable (function (ViewServiceInterface $viewService) use ($templateUrl) {
    return function ($request, $response) use ($viewService, $templateUrl) {
      $filename = $viewService->resolveTemplatePath ($templateUrl);
      return Http::response ($response, $viewService->loadFromFile ($filename)->render ());
    };
  });
}

/**
 * Generates a routable that, when invoked, will return an HTTP redirection response.
 *
 * @param string $url    The target URL.
 * @param int    $status HTTP status code.
 *                       <p>Valid redirection values should be:
 *                       <p>303 - See Other
 *                       <p>307 - Temporary Redirect
 *                       <p>308 - Permanent Redirect
 * @return Closure
 */
function redirect ($url, $status = 307)
{
  return function ($request, $response) use ($url, $status) {
    return Http::redirect ($response, $url, $status);
  };
}

/**
 * Generates a middleware that matches a given URL and on success, calls the given request handler.
 *
 * @param string   $url
 * @param callable $handler
 * @return Closure
 */
function route ($url, callable $handler)
{
  return function (ServerRequestInterface $request, $response, $next) use ($url, $handler) {
    return $request->getAttribute ('virtualUri') == $url ? $handler ($request, $response, $next) : $next ();
  };
}

/**
 * Generates a middleware that returns a simple HTML text response.
 *
 * @param string|callable $text The text to be returned.
 * @return Closure
 */
function simpleResponse ($text) {
  return function ($request, $response) use ($text) { return Http::response ($response, $text); };
}

/**
 * A shortcut to create a {@see FactoryRoutable}.
 *
 * @param callable $fn
 * @return FactoryRoutable
 */
function factory (callable $fn)
{
  return new FactoryRoutable ($fn);
}

/**
 * Returns a route to a controller method or function.
 *
 * <p>The callable will receive as arguments the route parameters, followed by the request and the response objects.
 * <p>It can return:
 * - a response object
 * - a string (sent as text/html)
 * - `null` to send an empty response
 * - arrays, objects or scalars will be sent as JSON.
 *
 * @param string|array|callable $ref Either a Closure, a 'Class::method' string or a ['Class', 'method'] array or an
 *                                   [$instance, 'method'] array.
 * @return FactoryRoutable
 * @throws Fault If an invalid data type is returned from the controller.
 */
function controller ($ref)
{
  return new FactoryRoutable (function (InjectorInterface $injector) use ($ref) {
    $ctrl = $injector->buildExecutable ($ref);
    return function (ServerRequestInterface $request, ResponseInterface $response) use ($ctrl) {
      $args   = array_merge (array_values (Http::getRouteParameters ($request)), [$request, $response]);
      $result = $ctrl (...$args);
      switch (true) {
        case $result instanceof ResponseInterface:
          return $result;
        case is_string ($result):
          break;
        case is_null ($result):
          $result = '';
          break;
        case is_array ($result):
        case is_object ($result):
        case is_scalar ($result):
          return Http::jsonResponse ($response, $result);
        default:
          throw new Fault (Faults::INVALID_RESPONSE_TYPE);
      }
      return Http::response ($response, $result);
    };
  });
}

/**
 * Outputs a formatted representation of the given arguments to the browser, clearing any existing output.
 * <p>This is useful for debugging.
 */
function dump ()
{
  if (!isCLI())
    echo "<pre>";
  ob_start ();
  call_user_func_array ('var_dump', func_get_args ());
  // Applies formatting if XDEBUG is not installed
  echo preg_replace_callback ('/^(\s*)\["?(.*?)"?\]=>\n\s*/m', function ($m) {
    list (, $space, $prop) = $m;
    return $space . str_pad ("$prop:", 30, ' ');
  }, ob_get_clean ());
  if (!isCLI())
    echo "</pre>";
}

/**
 * Outputs a stack trace up to the first call to this function with detailed timing and memory consumption information
 * about each function/method call.
 *
 * <p>It requires a logger panel named 'trace' to be defined.
 * <p>It also requires XDebug to be installed.
 *
 * @see PhpKit\WebConsole\DebugConsole\DebugConsole::trace
 * @return string
 * @throws Exception
 */
function trace ()
{
  PhpKit\WebConsole\DebugConsole\DebugConsole::trace ();
}
