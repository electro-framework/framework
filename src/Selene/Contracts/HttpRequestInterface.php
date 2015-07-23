<?php
namespace Selene\Contracts;

interface HttpRequestInterface
{

  /**
   * Sets the `Accept` header to JSON, performs the currently chained request, parses the response and returns it as a
   * native data structure.
   *
   * @param bool $associative When `true`, returned objects will be converted into associative arrays.
   * @return array|\StdClass The server's response as a native data structure.
   */
  function asJson ($associative = false);

  /**
   * Sets the `Accept` header to JSON, performs the currently chained request, parses the response and returns it as a
   * native data structure.
   *
   * @param bool $fullDOM When `true`, a DOMDocument object will be returned, SimpleXmlElement otherwise.
   * @param int  $options Bitwise OR of the libxml option constants.
   * @return \DOMDocument|\SimpleXmlElement|null|false The server's response as a native data structure.
   *                      `false` or `null` if the document could not be parsed.
   */
  function asXml ($fullDOM = false, $options = 0);

  /**
   * Sets the `Accept` header to the most common text types, performs the currently chained request and returns the
   * response text.
   *
   * @return string The server's response body.
   */
  function asText ();

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
   * Clears the current session.
   *
   * @return HttpRequestInterface
   */
  function clear ();

  /**
   * Sets the request method to `DELETE`.
   *
   * @param string $url
   * @return HttpRequestInterface
   */
  function delete ($url);

  /**
   * Sets the request method to `GET`.
   *
   * @param string $url
   * @return HttpRequestInterface
   */
  function get ($url);

  /**
   * Performs the currently chained request and returns the response text.
   *
   * > This method doesn't set the `Accept` header. You should set it manually.
   *
   * @return string The server's response body.
   */
  function go ();

  /**
   * Adds a header to the current request.
   *
   * @param string           $name
   * @param string|int|float $value
   * @return HttpRequestInterface
   */
  function header ($name, $value);

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
   * Sets the request method to `POST`.
   *
   * @param string $url
   * @return HttpRequestInterface
   */
  function post ($url);

  /**
   * Sets the request method to `PUT`.
   *
   * @param string $url
   * @return HttpRequestInterface
   */
  function put ($url);

  /**
   * Sets the request payload for POST or PUT requests.
   *
   * @param string $body
   * @return HttpRequestInterface
   */
  function send ($body);

}
