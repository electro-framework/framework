<?php
namespace Selene\Http;

/**
 * Represents an HTTP request, allows sending it, receive the response and parse it.
 * @package Selene\Http
 */
interface HttpRequestInterface
{
  /**
   * Sets the URL prefix for further requests.
   *
   * It appends a trailing slash if it is not present.
   * > Not used on the static request method.
   *
   * @param string $url
   * @return HttpRequestInterface
   */
  function baseUrl ($url);

  /**
   * Returns a new request based on the current request, keeping the current navigation session.
   *
   * The base URL, cookies and referrer are preserved. All other settings are initialized to their default values.
   *
   * @return HttpRequestInterface
   */
  function begin ();

  /**
   * Begins a new request by setting its method to `DELETE` and its URL to the specified one.
   *
   * > It clears the remaining request data (params, headers, etc).
   * > To issue a new request reusing the current data, call {@link HttpRequestInterface::send()} again.
   *
   * This method supports parametric URLs (ex: dynamic URL segments).
   * Any remaining arguments on the method call are injected into the URL on placeholders written in `sprintf` syntax.
   *
   * > To set URL parameters, use {@link HttpRequestInterface::param()} instead of this.
   *
   * Ex:
   * ```
   *   $req->delete ('posts/%s', $id)->send ()
   * ```
   * @param string $url
   * @param string ...$args URL paramet
   * @return HttpRequestInterface
   */
  function delete ($url);

  /**
   * Sets the `Accept` header to JSON and the response transformer to a JSON parser, for subsequent requests.
   *
   * @param bool $associative When `true`, returned objects will be converted into associative arrays.
   * @return HttpRequestInterface
   */
  function expectJson ($associative = false);

  /**
   * Sets the `Accept` header to the most common text types and the response transformer to `null`, on all future
   * requests.
   *
   * @return HttpRequestInterface
   */
  function expectText ();

  /**
   * Sets the `Accept` header to JSON and the response transformer to a XML parser, for subsequent requests.
   *
   * <p>{@link HttpRequestInterface::send()} will return {@link DOMDocument}|{@link SimpleXmlElement}|`null`|`false`.
   * <br>`null` or `false` may mean the document could not be parsed.
   *
   * @param bool $fullDOM When `true`, a DOMDocument object will be returned, SimpleXmlElement otherwise.
   * @param int  $options Bitwise OR of the libxml option constants.
   * @return HttpRequestInterface
   */
  function expectXml ($fullDOM = false, $options = 0);

  /**
   * Begins a new request by setting its method to `GET` and its URL to the specified one.
   *
   * > It clears the remaining request data (params, headers, etc).
   * > To issue a new request reusing the current data, call {@link HttpRequestInterface::send()} again.
   *
   * This method supports parametric URLs (ex: dynamic URL segments).
   * Any remaining arguments on the method call are injected into the URL on placeholders written in `sprintf` syntax.
   *
   * > To set URL parameters, use {@link HttpRequestInterface::param()} instead of this.
   *
   * Ex:
   * ```
   *   $req->get ('api/%s/%s', $name, $id)->send ()
   * ```
   * @param string $url
   * @param string ...$args Remaining arguments are injected into the URL on placeholders with `sprintf` syntax.
   * @return HttpRequestInterface
   */
  function get ($url);

  /**
   * Adds a header to the current request.
   *
   * @param string|string[]       $name
   * @param string|int|float|null $value If null, the header will be removed.
   * @return HttpRequestInterface
   */
  function header ($name, $value);

  /**
   * Adds multiple headers to the current request.
   *
   * @param  array $map A map of header names to header values.
   * @return HttpRequestInterface
   * @see    HttpRequestInterface::param()
   */
  function headers (array $map);

  /**
   * Sets the request's HTTP verb.
   *
   * @param string $verb One of `get|put|post|delete|patch|head|options`.
   * @return HttpRequestInterface
   */
  function method ($verb);

  /**
   * Adds an URL parameter to the current request.
   *
   * <p>Multiple parameters with the same name are allowed.
   * <p>Null values will cause the parameter to NOT be added.
   * <p>Empty strings will add a parameter with an empty value.
   *
   * @param string           $name
   * @param string|int|float $value
   * @return HttpRequestInterface
   */
  function param ($name, $value);

  /**
   * Adds multiple URL parameters to the current request.
   *
   * @param  array $map A map of parameter names to parameter values.
   * @return HttpRequestInterface
   * @see    HttpRequestInterface::param()
   */
  function params (array $map);

  /**
   * Begins a new request by setting its method to `POST` and its URL to the specified one.
   *
   * > It clears the remaining request data (params, headers, etc).
   * > To issue a new request reusing the current data, call {@link HttpRequestInterface::send()} again.
   *
   * This method supports parametric URLs (ex: dynamic URL segments).
   * Any remaining arguments on the method call are injected into the URL on placeholders written in `sprintf` syntax.
   *
   * > To set URL parameters, use {@link HttpRequestInterface::param()} instead of this.
   *
   * Ex:
   * ```
   *   $req->post ('authors/%s/posts', $author)->with ($data)->send ()
   * ```
   * @param string $url
   * @param string ...$args Remaining arguments are injected into the URL on placeholders with `sprintf` syntax.
   * @return HttpRequestInterface
   */
  function post ($url);

  /**
   * Begins a new request by setting its method to `PUT` and its URL to the specified one.
   *
   * > It clears the remaining request data (params, headers, etc).
   * > To issue a new request reusing the current data, call {@link HttpRequestInterface::send()} again.
   *
   * This method supports parametric URLs (ex: dynamic URL segments).
   * Any remaining arguments on the method call are injected into the URL on placeholders written in `sprintf` syntax.
   *
   * > To set URL parameters, use {@link HttpRequestInterface::param()} instead of this.
   *
   * Ex:
   * ```
   *   $req->put ('authors/%s/posts/%s', $author, $id)->with ($data)->send ()
   * ```
   * @param string $url
   * @param string ...$args Remaining arguments are injected into the URL on placeholders with `sprintf` syntax.
   * @return HttpRequestInterface
   */
  function put ($url);

  /**
   * Performs the currently chained request and returns the response text.
   *
   * > This method doesn't set the `Accept` header. You should set it previously via the asXXX() methods.
   *
   * @return mixed The server's response body, eventually transformed.
   */
  function send ();

  /**
   * Sets the response transformer function.
   *
   * @param callable $callback A function to transform the response. It receives the raw response text as argument.
   * @return HttpRequestInterface
   */
  function transform ($callback);

  /**
   * Sets the URL of the request.
   *
   * This method supports parametric URLs (ex: dynamic URL segments).
   * Any remaining arguments on the method call are injected into the URL on placeholders written in `sprintf` syntax.
   *
   * > To set URL parameters, use {@link HttpRequestInterface::param()} instead of this.
   *
   * Ex:
   * ```
   *   $req->method ('get')->url ('api/%s/%s', $name, $id)->send ()
   * ```
   * @param string $url
   * @param string ...$args Remaining arguments are injected into the URL on placeholders with `sprintf` syntax.
   * @return HttpRequestInterface
   */
  function url ($url);

  /**
   * Sets the request payload for POST or PUT requests.
   *
   * @param string $body
   * @return HttpRequestInterface
   */
  function with ($body);
}
