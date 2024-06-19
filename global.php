<?php
use Electro\Exceptions\Fault;
use Electro\Faults\Faults;
use Electro\Http\Lib\Http;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\MiddlewareStackInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\Views\ViewModelInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
use Electro\Interop\InjectableFunction;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Returns a middleware that, when executed, instantiates a class from the injector and calls a request handler method
 * on it.
 *
 * @param string $class
 * @param string $method
 * @return InjectableFunction
 */
function handlerMethod ($class, $method)
{
  return injectableWrapper (function (InjectorInterface $injector) use ($class, $method) {
    $o = $injector->make ($class);
    return _fn([$o, $method]);
	});
}

/**
 * Returns a route to a controller method or function.
 *
 * <p>The callback will receive as arguments (in order):
 * - The parsed request body (for HTTP methods other than GET),
 * - the route parameters,
 * - the request and response objects.
 *
 * > **Note:** the callback, on its function signature, may omit trailing parameters that it doesn't need.
 *
 * <p>The value returned from the callback (which can be any kind of value) will be converted to a
 * {@see ResponseInterface} and that will be the response returned from the middleware this function generates.
 *
 * <p>See {@see autoResponse()} for more information about the automated response generation and the conversion process.
 *
 * @param callable $ref Either a Closure, a 'Class::method' string or a ['Class', 'method'] array or an
 *                      [$instance, 'method'] array.
 * @return \Electro\Interop\InjectableFunction
 * @throws Fault If an invalid data type is returned from the controller.
 */
function controller ($ref)
{
  return injectableWrapper (function (InjectorInterface $injector) use ($ref) {
    $ctrl = $injector->buildExecutable ($ref);
    return autoResponse (function (ServerRequestInterface $request, ResponseInterface $response) use ($ctrl) {
      $args = array_merge (
        $request->getMethod () != 'GET' ? [$request->getParsedBody ()] : [],
        array_values (Http::getRouteParameters ($request)),
        [$request, $response]
      );
      return $ctrl (...$args);
    });
  });
}

/**
 * Returns a middleware that invokes the given provisionable callable and processes the resulting return value in order
 * to automatically generate the most appropriate HTTP response from it.
 *
 * <p>The conversion to a {@see ResponseInterface} is performed by these rules:
 *
 * Callback return value | Generated response
 * ----------------------|-------------------
 * a response object     | no change
 * a callable/middleware | no change
 * a string              | a text/html response
 * `null`                | an empty response
 * an array              | a JSON response
 * an object             | a JSON response
 * a scalar (ex. boolean)| a JSON response
 *
 * @param callable $handler function ($request, $response, $next):mixed
 * @return InjectableFunction
 */
function autoResponse (callable $handler)
{
  return nonMiddleware ($handler, function ($request, ResponseInterface $response, $result) {
    switch (true) {
      case $result instanceof ResponseInterface:
      case is_callable ($result):
        return $result;
        break;
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
  });
}

/**
 * Generates a middleware that executes a non-middleware provisionable callable and feeds its result (which may be of
 * any type) to the given consumer middleware.
 *
 * <p>While the callable is an {@see InjectableFunction}, it is called until another type of callable is obtained,
 * then that value is used as the final callable to be invoked.
 *
 * <p>The return value from this function is a middleware that, when executed, returns the result of invoking the provided
 * (or resolved from the provided) callable.
 *
 * @param callable $fn       function ($request, $response, $next):mixed
 * @param callable $consumer function ($request, $response, $value):ResponseInterface
 * @return InjectableFunction
 */
function nonMiddleware (callable $fn, callable $consumer)
{
  return injectableHandler (
    function (ServerRequestInterface $request, ResponseInterface $response, $next, InjectorInterface $injector)
    use ($fn, $consumer) {
      while ($fn instanceof InjectableFunction)
        $fn = $injector->execute ($fn ());
      return $consumer ($request, $response, $fn ($request, $response, $next));
    });
}

/**
 * Creates a middleware that is a stack of middleware callables.
 *
 * @param callable[] ...$middleware
 * @return \Electro\Interop\InjectableFunction
 */
function stack (...$middleware)
{
  return injectableWrapper (function (MiddlewareStackInterface $stack) use ($middleware) {
    return $stack->set ($middleware);
  });
}

/**
 * Middleware to initialize the navigation service with the current request.
 *
 * @return InjectableFunction
 */
function navigationMiddleware ()
{
  return injectableHandler (function ($request, $response, $next, NavigationInterface $navigation) {
    $navigation->setRequest ($request);
    return $next();
  });
}

/**
 * Creates a middleware that handles an application page, both in rendering (GET) and in handling actions (POST).
 *
 * @param string $templateUrl
 * @param null   $actionHandler
 * @return \Electro\Interop\InjectableFunction
 */
function page ($templateUrl, $actionHandler = null)
{
  return stack (
    method ('GET', view ($templateUrl)),
    $actionHandler ? method ('POST', $actionHandler) : null
  );
}

function action ($map)
{
  return 0;
}

/**
 * Creates a middleware that renders a page with a form and handles its submission.
 *
 * TODO: implement this
 *
 * @param string $templateUrl
 * @return \Electro\Interop\InjectableFunction
 */
function formPage ($templateUrl)
{
  return page ($templateUrl, action ([
    'submit' => stack (redirectUp ()),
  ]));
}

/**
 * Generates a routable that, when invoked, will return an HTTP redirection response.
 *
 * @param string $url    The target URL.
 * @param int    $status HTTP status code.
 *                       <p>Valid redirection values should be:
 *                       <p>302 - Found (client will always send a GET request and original URL will not be cached)
 *                       <p>303 - See Other
 *                       <p>307 - Temporary Redirect
 *                       <p>308 - Permanent Redirect
 * @return Closure
 */
function redirect ($url, $status = 302)
{
  return function ($request, $response) use ($url, $status) {
    return Http::redirect ($response, $url, $status);
  };
}

/**
 * Creates a middleware that redirects to the URL of the specified navigation link.
 *
 * @param string $navigationId
 * @return InjectableFunction
 */
function redirectTo ($navigationId)
{
  return injectableHandler (function ($request, $response, $next, NavigationInterface $navigation) use ($navigationId) {
    $navigation->setRequest ($request);
    return Http::redirect ($response, $navigation[$navigationId]->absoluteUrl ());
  });
}

/**
 * Creates a middleware that redirects to the URL of current navigation link's parent.
 *
 * @return \Electro\Interop\InjectableFunction
 */
function redirectUp ()
{
  return injectableHandler (function ($request, $response, $next, NavigationInterface $navigation) {
    $navigation->setRequest ($request);
    return Http::redirect ($response, $navigation->currentLink ()->parent ()->absoluteUrl ());
  });
}

/**
 * Creates a middleware that redirects to the current URL.
 *
 * @return Closure
 */
function redirectToSelf ()
{
  return function (ServerRequestInterface $request, $response) {
    return Http::redirect ($response, (string)$request->getUri ());
  };
}

/**
 * Generates a routable that, when invoked, will load and render specified view template.
 *
 * <p>Use this to define routes for simple pages that only have a view model (optionally) and no controller logic.
 *
 * @param string $templateUrl
 * @return \Electro\Interop\InjectableFunction
 */
function view ($templateUrl)
{
  return injectableHandler (function (ServerRequestInterface $request, $response, $next,
                                      ViewServiceInterface $viewService, InjectorInterface $injector)
  use ($templateUrl) {
    $view      = $viewService->loadFromFile ($templateUrl);
    $viewModel = initPageViewModel ($viewService->createViewModelFor ($view, true), $request);
    return Http::response ($response, $view->render ($viewModel));
  });
}

/**
 * Initializes a view model for a root template (i.e. the first to be rendered on response to a HTTP request).
 *
 * @param ViewModelInterface|null $viewModel
 * @param ServerRequestInterface  $request
 * @return ViewModelInterface
 */
function initPageViewModel (ViewModelInterface $viewModel = null, ServerRequestInterface $request)
{
  if (isset($viewModel)) {
    if (!is_object ($viewModel) || !($viewModel instanceof ViewModelInterface))
      throw new RuntimeException(sprintf ("Invalid view model type: <kbd>%s</kbd>)", typeOf ($viewModel)));
    $props              = get ($viewModel, 'props');
    $params             = Http::getRouteParameters ($request);
    $viewModel['props'] = $props ? array_merge ($props, $params) : $params;
    $viewModel['fetch'] = $request->getAttribute ('isFetch');
    $viewModel->init ();
  }
  return $viewModel;
}

/**
 * A middleware that sets a listener for the CREATE_VIEW_MODEL event.
 *
 * @param callable $fn function (ViewModelInterface, ViewInterface)
 * @return Closure|InjectableFunction
 */
function onCreateViewModel (callable $fn)
{
  return injectableHandler (function ($req, $res, callable $next, ViewServiceInterface $viewService) use ($fn) {
    $viewService->onCreateViewModel ($fn);
    return $next();
  });
}

/**
 * An empty (no operation) middleware that just forwards the request to the next middleware on the stack.
 *
 * @return Closure
 */
function forward ()
{
  return function ($req, $res, $next) {
    return $next ();
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
  $cache = null; // speeds up subsequent route() calls.
  return function (ServerRequestInterface $request, $response, $next) use ($url, $handler, &$cache) {
    return $url == ($cache
      ?: (
        $cache = str_replace ($request->getAttribute ('appBaseUri') . '/', '', $request->getAttribute ('virtualUri'))
      )
    )
      ? $handler : $next ();
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
    return $request->getMethod () == $method ? $handler : $next ();
  };
}

/**
 * Creates a middleware that ends the middleware stack and begins the chained response returning sequence.
 *
 * @return Closure
 */
function back ()
{
  // Note: routing middleware will not provide any arguments to this function.
  return function ($req = null, $res = null) {
    return $res;
  };
}

/**
 * Generates a middleware that returns a simple HTML text response.
 *
 * @param string|callable $text The text to be returned.
 * @return Closure
 */
function htmlResponse ($text)
{
  return function ($request, $response) use ($text) { return Http::response ($response, $text); };
}

/**
 * A shortcut to create an {@see InjectableFunction}.
 *
 * ###### Ex:
 *     injectableWrapper (function (Service1 $a, Service2 $b) {
 *       return function ($req, $res, $next) use ($a, $b) { ... };
 *     })
 *
 * @param callable $fn
 * @return \Electro\Interop\InjectableFunction
 */
function injectableWrapper (callable $fn)
{
  return new InjectableFunction ($fn);
}

/**
 * Creates an injectable request handler directly from a handler function.
 *
 * <p>The given handler should define extra injectable parameters after the 3 standard middleware ones ($request,
 * $response and $next).
 *
 * ###### Ex:
 *     injectableHandler (function ($req, $res, $next, Service1 $a, Service2 $b) { ... })
 *
 * @param callable $fn
 * @return \Electro\Interop\InjectableFunction|\Closure
 */
function injectableHandler (callable $fn)
{
  $ref = reflectionOfCallable ($fn);
  /** @var \ReflectionParameter[] $argsRef */
  $argsRef = $ref->getParameters ();
  if (count ($argsRef) < 4)
    return $fn;
  $argsRef = array_slice ($argsRef, 3); // Discard $req, $res and $next
  return new InjectableFunction (function (InjectorInterface $injector) use ($argsRef, $fn) {
    $args = [];
    foreach ($argsRef as $ar) {
      $type = $ar->getType();
      if ($type)
        $args[] = $injector->make ($type->getName ());
      else throw new \InvalidArgumentException ("Untyped arguments are not supported for injectable handlers");
    }
    return function ($req, $res, $next) use ($args, $fn) {
      return $fn ($req, $res, $next, ...$args);
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
  // $o = str_replace ('[', '[', $o); // to prevent colision with color escape codes
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
           color ('dark yellow', str_pad ($prop, $num ? 4 : 28, ' ', $num ? STR_PAD_LEFT : STR_PAD_RIGHT)) .
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
 * Returns a textual representation of the given argument.
 * <p>This is useful for debugging.
 */
function getDump ($value)
{
  $o = json_encode ($value,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR);
  // Unquote keys
  $o = preg_replace ('/^(\s*)"([^"]+)": /m', '$1$2: ', $o);
  // Compact arrays that have a single value
  $o = preg_replace ('/(^|: )\[\s+(\S+)\s+]/', '$1[ $2 ]', $o);
  return $o;
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
