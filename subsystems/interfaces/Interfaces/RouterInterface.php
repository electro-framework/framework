<?php
namespace Selenia\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A service that assists in routing an HTTP request to one or more request handlers.
 * <p>A handler (also called a *routable*) may generate an HTTP response and/or route to other handlers.
 * <p>The request will traverse a graph of interconnected handlers, until a full HTTP response is generated.
 * > **Note:** A *routable* is a callable that implements the `RoutableInterface` call signature.
 */
interface RouterInterface
{
  /**
   * Invokes the callable whose key matches the next location and returns its response, or returns false if no match
   * occurred.
   * @param callable[] $map A <kbd>[string => callable]</kbd> map.
   *                        <p>Each callable should have a <kbd>RouterInterface</kbd> signature:
   *                        <code>ResponseInterface|false (RouterInterface $router)</code>
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
   * <dt><b><kbd>/regexp/modifiers</kbd></b>
   * <dd>Matches a regular expression.
   * <br>&nbsp;
   *
   * <dt><b><kbd>literal</kbd></b>
   * <dd>Matches the given literal string.
   * <br>&nbsp;
   *
   * <dt><b><kbd>:param</kbd></b>
   * <dd>Matches anything and saves it as a named route parameter.
   * <br>&nbsp;
   *
   * <dt><b><kbd>/regexp/modifiers:param</kbd></b>
   * <dd>Matches a regular expression and saves capture #0 (or #1 if defined) and saves it as a named route parameter.
   * <br>&nbsp;
   *
   * <dt><b><kbd>literal:param</kbd></b>
   * <dd>Matches the given literal string and saves it as a named route parameter.
   * <br>&nbsp;
   *
   * </dl>
   *
   * ### Notes:
   * - The routable will be called only if the next location matches the expression.
   * - The location value is stored as a named route parameter only when the routable is invoked.
   *
   * @param string   $pattern
   * @param callable $routable A callback with a <kbd>RouterInterface</kbd> signature: <code>ResponseInterface|false
   *                           (RouterInterface $router)</code>
   * @return ResponseInterface|false <kbd>false</kbd> if no match.
   */
  function match ($pattern, callable $routable);

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
   * @param string   $methods A pipe-delimited list of HTTP methods, or <kbd>'*'</kbd> to match any method.
   *                          <p>Ex: <kbd>'GET|POST'</kbd>
   *                          <p>Some valid verbs: <kbd>GET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS</kbd>
   * @param callable $routable
   * @return mixed
   */
  function on ($methods, callable $routable);

  /**
   * Invokes a routable if:
   * - the current route location is the last one (the route target/destination)
   * - the request matches the specified list of HTTP methods.
   *
   * @param string   $methods A pipe-delimited list of HTTP methods, or <kbd>'*'</kbd> to match any method.
   *                          <p>Ex: <kbd>'GET|POST'</kbd>
   *                          <p>Some valid verbs: <kbd>GET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS</kbd>
   * @param callable $routable
   * @return mixed
   */
  function onTarget ($methods, callable $routable);

  /**
   * Creates a new redirection HTTP response.
   * @return RedirectionInterface;
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
