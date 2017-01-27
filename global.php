<?php
use Electro\Exceptions\Fault;
use Electro\Faults\Faults;
use Electro\Http\Lib\Http;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\MiddlewareStackInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
use Electro\Routing\Lib\FactoryRoutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


function stack (...$middleware)
{
  return new FactoryRoutable (function (MiddlewareStackInterface $stack) use ($middleware) {
    return $stack->set ($middleware);
  });
}

function page ($templateUrl, $actionHandler = null)
{
  return view ($templateUrl);
  return stack (
    method ('GET', view ($templateUrl)),
    method ('POST', $actionHandler)
  );
}

function action ($map)
{
  return 0;
}

function formPage ($templateUrl)
{
  return page ($templateUrl, action ([
    'submit' => stack (redirectUp ()),
  ]));
}

function redirectUp ()
{
  return new FactoryRoutable (function (NavigationInterface $navigation) {
    return function ($request, $response) use ($navigation) {
      $navigation->getCurrentTrail ();
      return Http::redirect ($response, $navigation->currentLink ()->parent ()->url ());
    };
  });
}

function redirectToSelf ()
{
  return function (ServerRequestInterface $request, $response) {
    return Http::redirect ($response, (string)$request->getUri ());
  };
}

/**
 * Generates a routable that, when invoked, will return a generic PageComponent with the specified template as a view.
 *
 * <p>Use this to define routes for simple pages that only have a view model (optionally) and no controller logic.
 *
 * @param string $templateUrl
 * @return FactoryRoutable
 */
function view ($templateUrl)
{
  return new FactoryRoutable (function (ViewServiceInterface $viewService, InjectorInterface $injector) use (
    $templateUrl
  ) {
    return function ($request, $response) use ($viewService, $templateUrl, $injector) {
      $view      = $viewService->loadFromFile ($templateUrl, ['page' => true]);
      $viewModel = $viewService->createViewModelFor ($view);
      return Http::response ($response, $view->render ($viewModel));
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
    return $request->getAttribute ('virtualUri') == $url ? $handler ($request, $response, back ()) : $next ();
  };
}

/**
 * Generates a middleware that matches a given HTTP verb and on success, calls the given request handler, otherwise it
 * calls the next middleware.
 *
 * @param string   $method
 * @param callable $handler
 * @return Closure
 */
function method ($method, callable $handler)
{
  return function (ServerRequestInterface $request, $response, $next) use ($method, $handler) {
    return $request->getMethod () == $method ? $handler ($request, $response, back ()) : $next ();
  };
}

/**
 * Creates a middleware that simply reverses the direction of execution on the middlware stack.
 *
 * @return Closure
 */
function back ()
{
  return function ($req, $res) {
    return $res;
  };
}

/**
 * Generates a middleware that returns a simple HTML text response.
 *
 * @param string|callable $text The text to be returned.
 * @return Closure
 */
function simpleResponse ($text)
{
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
  error_clear_last ();
  if (!isCLI ())
    echo "<pre>";
  ob_start ();
  call_user_func_array ('var_dump', func_get_args ());
  $o = ob_get_clean ();
  $o = str_replace ('[', '[', $o); // to prevent colision with color escape codes
  $o = preg_replace ('/\{\s*\}/', '{}', $o); // condense empty arrays
  $o = preg_replace ('/":".*?":(private|protected)/', color ('dark grey', ':$1'), $o); // condense empty arrays
  // Applies formatting if XDEBUG is not installed
  $SEP = color ('dark grey', '|');
  $o   = preg_replace_callback ('/^(\s*)\["?(.*?)"?\]=>\n\s*(\S+) *(\S)?/m', function ($m) use ($SEP) {
    $m[] = '';
    list (, $space, $prop, $type, $next) = $m;
    $z = explode ('(', $type, 2);
    if (count ($z) > 1) {
      list ($type, $len) = $z;
      $len  = color ('dark cyan', " ($len");
      $type = $type . $len;
    }
    $num = ctype_digit ($prop[0]);
    return $space . $SEP .
           color ('dark yellow', str_pad ($prop, $num ? 4 : 22, ' ', $num ? STR_PAD_LEFT : STR_PAD_RIGHT)) .
           " $SEP " . color ('dark green', str_pad ($type, 25, ' ')) . (strlen ($next) ? "$SEP $next" : '');
  }, $o);
  $o   = preg_replace ('/[\{\}§\]]/', color ('red', '$0'), $o);
  $o   = str_replace ('"', color ('dark cyan', '"'), $o);
  $o   = preg_replace ('/^(\s*object)\((.*?)\)(.*?(?=\{))/m',
    '$1(' . color ('dark purple', '$2') . ')' . color ('dark cyan', '$3'), $o);
  $o   =
    preg_replace ('/^(\s*\w+)\((\d+)\)/m', str_pad (color ('dark green', '$1') . color ('dark cyan', ' ($2)'), 31, ' '),
      $o);
  echo $o;
  if (!isCLI ())
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
 * @return void
 * @throws Exception
 */
function trace ()
{
  PhpKit\WebConsole\DebugConsole\DebugConsole::trace ();
}
