<?php
namespace Selenia\Interfaces\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A service that assists in routing an HTTP request to one or more request handlers.
 * <p>A handler (also called a *routable*) may generate an HTTP response and/or route to other handlers.
 * <p>The request will traverse a graph of interconnected handlers, until a full HTTP response is generated or the
 * graph is exhausted.
 *
 * ### Notes
 * - Instances implementing this interface **MUST** be immutable objects.
 * - `next()` creates new instances.
 * - A *routable* is a callable that implements the `RoutableInterface` call signature.
 *
 * ### Routables
 *
 * On methods of this interface, a parameter of routable type is a `callable` that can be invokable classe, object,
 * method or function. It will be dependency-injected when invoked.
 * <p>Usually it will have a <kbd>RouterInterface</kbd>-compatible signature:
 * <code>ResponseInterface|false (RouterInterface $router)</code>
 * > **Note:** when being invoked by the router, if a function lists `RouterInterface` as an argument, it will be
 * injected with the **current** router instance.
 *
 * > **Note:** when instantiating an invokable class, its constructor will also be dependency-injected.
 *
 * ### Configuring routables
 *
 * Sometimes you may wish to configure a routable class instance before invoking it (ex. configuring a controller
 * or a component). In that case, you may supply a configuration function instead of the target routable.
 * <p>The setup function can request any required dependency trough its parameters. One of those parameters should
 * usually be an instance of the desired routable class.
 * <p>The setup function **MUST** return the configured routable instance. The router will then invoke it passing
 * itself as the sole argument.
 */
interface RouterInterface
{
  /**
   * Invokes the callable whose key matches the next location and returns its response, or returns false if no match
   * occurred.
   * @param array $map A <kbd>[string => routable]</kbd> map.
   *
   * @return ResponseInterface|false
   */
  function dispatch (array $map);

  /**
   * Matches the next location via a pattern and invokes the given routable if it succeeds.
   * <p>It also optionally saves the location string into a named route parameter, retrievable via `route()->params()`.
   *
   * ### Pattern syntax
   *
   * <dl>
   *
   * <dt><i>(empty string)</i>
   * <dd>Matches anything.
   * <br>&nbsp;
   *
   * <dt><b><kbd>literal</kbd></b>
   * <dd>Matches the given literal string.
   * <br>Ex: <kbd>'users'</kbd>
   * <br>&nbsp;
   *
   * <dt><b><kbd>{param}</kbd></b>
   * <dd>Matches anything and saves it as a named route parameter.
   * <br>Ex: <kbd>'{userId}'</kbd>
   * <br>&nbsp;
   *
   * <dt><b><kbd>/regexp/modifiers</kbd></b>
   * <dd>Matches a regular expression.
   * <br>Ex: <kbd>'/^user\d+$/i'</kbd>
   * <br>&nbsp;
   *
   * <dt><b><kbd>{param:/regexp/modifiers}</kbd></b>
   * <dd>Matches a regular expression and saves capture #0 (or #1 if defined) and saves it as a named route parameter.
   * <br>On the following example, userId = extracted numeric value (\d+):
   * <br><kbd>'{userId:/^user(\d+)$/}'</kbd>
   * <br>&nbsp;
   *
   * <dt><b><kbd>{param:literal}</kbd></b>
   * <dd>Matches the given literal string and saves it as a named route parameter.
   * <br>Ex: <kbd>'{userId:none}'</kbd>
   * <br>&nbsp;
   *
   * </dl>
   *
   * ### Notes:
   * - The routable will be called only if the next location matches the expression.
   * - The location value is stored as a named route parameter only when the routable is invoked.
   *
   * @param string               $methods  A pipe-delimited list of HTTP methods, or <kbd>'*'</kbd> to match any
   *                                       method.
   *                                       <p>Ex: <kbd>'GET|POST'</kbd>
   *                                       <p>Some valid verbs: <kbd>GET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS</kbd>
   * @param string               $pattern  As explained above.
   * @param callable|string|null $routable A callback with a <kbd>RouterInterface</kbd> signature:
   *                                       <code>ResponseInterface|false (RouterInterface $router)</code>
   *                                       If a string, it should be the name of an invokable class, which will be
   *                                       instantiated trough dependency injection.
   *                                       If <kbd>null</kbd>, this method will return either <kbd>true|false</kbd>.
   * @return ResponseInterface|bool        <kbd>false</kbd> if no match.
   *                                       <p>If there's a match, it returns either
   *                                       <kbd>true|ResponseInterface</kbd>, depending on the absence/presence of the
   *                                       <kbd>$routable</kbd> argument.
   */
  function match ($methods, $pattern, callable $routable = null);

  /**
   * Invokes a routable if the remaining URL path starting from the current location matches a given path.
   * <p>The routable will receive a router instance whose route begins **after** the matched path.
   * > This is used internally by the routing middleware when matching registered module routers, after the main router
   * fails to match anything.
   * >> Ex:
   * >> <p>A module may register a router for the `'admin/stats'` prefix. When matching `'admin/stats/demo/1'`, the
   * module will be invoked with a route whose current location is `'demo'`.
   * @param string          $path     Ex: <kbd>'admin/users'</kbd> is equivalent to the
   *                                  <kbd>'/^admin\/users(?=\/|$)/'</kbd> regexp.
   * @param callable|string $routable If a string, it should be the name of an invokable class, which will be
   *                                  instantiated trough dependency injection.
   * @return RouteInterface|false     <kbd>false</kbd> if no match.
   */
  function matchPrefix ($path, $routable);

  /**
   * Passes the request trough a middleware stack.
   *
   * <p>The argument to this method can be of the following types:
   * - A callable (string|array|function) - it should be a `MiddlewareStackInterface|MiddlewareInterface` instance or a
   * function that has a compatible call signature.
   * - An array; this method will internally create a `MiddlewareStack` from that array.
   * - A class name; it will be instantiated using dependency injection.
   * - `null`; no operation - useful when using a conditional expression as argument.
   *
   * @param MiddlewareInterface|string|array|null $middleware If an array, its elements can be
   *                                                          <kbd>string|callable</kbd>.
   *                                                          <p>If a string, it must be a callable or a class name.
   * @return $this
   */
  function middleware ($middleware);

  /**
   * Advance the route's current location to the next one.
   * <p>If the route is already at the end, further matches will all fail.
   * @param ResponseInterface $response If provided, the given response will be used as the current response from this
   *                                    point on.
   * @return static A new instance of this class.
   */
  function next (ResponseInterface $response = null);

  /**
   * Invokes a routable if the request matches a list of HTTP methods.
   * @param string          $methods  A pipe-delimited list of HTTP methods, or <kbd>'*'</kbd> to match any method.
   *                                  <p>Ex: <kbd>'GET|POST'</kbd>
   *                                  <p>Some valid verbs: <kbd>GET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS</kbd>
   * @param callable|string $routable If a string, it should be the name of an invokable class, which will be
   *                                  instantiated trough dependency injection.
   * @return $this
   */
  function onTraverse ($methods, $routable);

  /**
   * Invokes a routable (or returns `true` if none is given) if both:
   * - the current route location is the last one (the route target/destination),
   * - the request matches the specified list of HTTP methods.
   *
   * @param string               $methods  A pipe-delimited list of HTTP methods, or <kbd>'*'</kbd> to match any method.
   *                                       <p>Ex: <kbd>'GET|POST'</kbd>
   *                                       <p>Some valid verbs: <kbd>GET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS</kbd>
   * @param callable|string|null $routable If a string, it should be the name of an invokable class, which will be
   *                                       instantiated trough dependency injection.
   *                                       <p>If <kbd>null</kbd>, this method will return either <kbd>true|false</kbd>.
   * @return $this
   */
  function on ($methods, $routable = null);

  /**
   * Creates a new redirection HTTP response.
   *
   * <p>This is a convenience method that saves you from having to inject the redirection service on routers.
   * @return RedirectionInterface
   */
  function redirection ();

  /**
   * The HTTP request being routed.
   *
   * @return ServerRequestInterface
   */
  function request ();

  /**
   * The HTTP response that was generated so far.
   * <p>It is initially empty, but some routables along the route may generate and accumulate partial content for it.
   *
   * @return ResponseInterface
   */
  function response ();

  /**
   * The target route for this router instance.
   * @return RouteInterface
   */
  function route ();

}
