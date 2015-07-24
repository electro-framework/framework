<?php
namespace Selene\Http;
use DOMDocument;
use InvalidArgumentException;
use SimpleXMLElement;

/**
 * A very lightweight PHP 5.4+ library for issuing HTTP requests, with a fluent API.
 * <p>
 * > This is a high level interface to the `CURL` PHP extension, which must be enabled on your PHP interpreter.
 * @package Selene\Http
 */
class HttpClient implements HttpClientInterface
{
  /**
   * If `true`, checks all responses for a 200 status and throws an exception if it doesn't match.
   * @var bool
   */
  public $autoCheck = true;
  /**
   * @var string The URL prefix for all instance requests (not used on the static request method).
   * If not empty, it must always end with a slash.
   */
  public $baseUrl = '';
  /**
   * The request payload for POST or PUT requests.
   * @var string
   */
  public $body;
  /**
   * @var string The cookies to be sent on further requests.
   */
  public $cookieJar = [];
  /**
   * The current request's HTTP headers.
   *
   * The array is a map of header names to header values.
   *
   * > **FOR READING ONLY**
   * > <p>Use {@link Selene\HttpRequest::header()} to modify this property.
   * @var string[]
   */
  public $headers = [];
  /**
   * The current request method.
   *
   * > **FOR READING ONLY**
   * > <p>Use `get(), post(), put()` or `delete()` to modify this property.
   * @var string
   */
  public $method;
  /**
   * The current request's URL parameters.
   *
   * The array is a map of parameter names to parameter values. Values are URL-encoded strings or string arrays.
   * For array values, multiple parameters with the same name will be appended to the URL.
   *
   * > **FOR READING ONLY**
   * > <p>Use {@link Selene\HttpRequest::param()} to modify this property.
   * @var string[]
   */
  public $params = [];
  /**
   * @var string The last response HTTP headers as sent by the remote host.
   */
  public $rawResponseHeaders;
  /**
   * @var string The last requested URL.
   */
  public $referrer;
  /**
   * @var array The current cookies as set by the remote host.
   */
  public $responseCookies = [];
  /**
   * @var array The last response HTTP headers.
   */
  public $responseHeaders;
  /**
   * @var int The last response HTTP status.
   */
  public $responseStatus;
  /**
   * The current request URL, relative to {@link $baseUrl}.
   * > **FOR READING ONLY**
   * > <p>Use {@link Selene\HttpRequest::url()} to modify this property.
   * @var string
   */
  public $url;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @var callback A function to transform the response. Receives as argument the raw response text.
   */
  private $transform;

  function __construct ($baseUrl = '')
  {
    $this->baseUrl ($baseUrl);
  }

  /**
   * Static utility function. can be overridden on subclasses and it will be used by the respective instances.
   * @param array $headers
   * @return array
   */
  protected static function headersMapToList (array $headers)
  {
    return array_map (function ($name, $val) {
      if (is_array ($val))
        $val = implode (',', $val);
      return "$name: $val";
    }, array_keys ($headers), array_values ($headers));
  }

  /**
   * Static utility function. It can be overridden on subclasses and it will be used by the respective instances.
   * @param array $params
   * @return string
   */
  protected static function paramsToQueryString (array $params)
  {
    return empty($params) ? '' : '?' . http_build_query ($params);
  }

  /**
   * Retrieve a web resource from the given URL, optionally sending data.
   *
   * @param string       $url
   * @param string       $method
   * @param array|string $postData
   * @param array        $headers
   * @return array Array containing keys 'response', 'responseHeaders' and 'requestHeaders'.
   * @throws InvalidArgumentException If $method is not GET, POST, PUT or DELETE.
   */
  protected static function request ($url, $method = 'GET', $postData = null, $headers = [])
  {
    $methodArg = true;
    switch ($method) {
      case 'GET':
        $method = CURLOPT_HTTPGET;
        break;
      case 'POST':
        $method = CURLOPT_POST;
        break;
      case 'PUT':
        $method = CURLOPT_PUT;
        break;
      case 'DELETE':
        $method    = CURLOPT_CUSTOMREQUEST;
        $methodArg = 'DELETE';
        break;
      default:
        throw new InvalidArgumentException("Bad HTTP method: $method");
    }

    if (is_array ($postData))
      $postData = http_build_query ($postData);

    $ch = curl_init ($url);

    curl_setopt ($ch, $method, $methodArg);
    // Include the headers in the output.
    curl_setopt ($ch, CURLOPT_HEADER, true);
    // Track the handle's request string.
    curl_setopt ($ch, CURLINFO_HEADER_OUT, true);
    if (isset($postData))
      // The full data to post in a HTTP "POST" operation.
      curl_setopt ($ch, CURLOPT_POSTFIELDS, $postData);
    // An array of HTTP header fields to set.
    curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers);
    // Return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
    // Follow redirects.
    curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);

    //list($responseHeaders, $body) = explode ("\r\n\r\n", curl_exec ($ch), 2);
    list($responseHeaders, $body) = explode ("\r\n\r\n", curl_exec ($ch), 2);
    $requestHeaders = curl_getinfo ($ch, CURLINFO_HEADER_OUT);

    preg_match ('#^HTTP/[\d\.]+\s+(\d+)#', $responseHeaders, $m);
    $status = count ($m) > 1 ? intval ($m[1]) : 0;

    curl_close ($ch);

    return [
      'response'        => $body,
      'responseHeaders' => $responseHeaders,
      'requestHeaders'  => $requestHeaders,
      'status'          => $status,
    ];
  }

  function baseUrl ($url)
  {
    if ($url && $url[strlen ($url) - 1] != '/')
      $url .= '/';
    $this->baseUrl = $url;
    return /** HttpRequestInterface */
      $this;
  }

  function begin ()
  {
    $req            = new static ($this->baseUrl);
    $req->referrer  = $this->referrer;
    $req->cookieJar = $this->cookieJar;
    return $req;
  }

  /**
   * Retrieves a web resource from the given URL, optionally sending data.
   *
   * The request is made in the context of the current navigation session.
   * Cookies and referrers are automatically handled.
   *
   * > This is the low level interface to request sending.
   * > Use {@see send()} and the other chainable methods instead for a higher level fluid interface.
   *
   * @param string       $url
   * @param string       $method
   * @param array|string $postData
   * @param array        $headers
   * @return string
   * @throws HttpException
   */
  function call ($url, $method = 'GET', $postData = null, $headers = [])
  {
    // Prepare request.

    if (!empty($this->cookieJar))
      $headers[] = "Cookie: " . implode ('; ', $this->cookieJar);

    if (isset($this->referrer))
      $headers[] = "Referer: $this->referrer";

    // Perform request.

    $response = self::request ($this->baseUrl . $url, $method, $postData, $headers);

    // Parse response.

    $this->responseStatus = (int)$response['status'];
    if ($this->autoCheck && $this->responseStatus != 200) {
      $err = print_r ($response, true);
      throw new HttpException($err, $this->responseStatus);
    }
    $this->referrer           = $url;
    $this->rawResponseHeaders = $response['responseHeaders'];
    $rawHeaders               = preg_split ('/\s*$\s*/m', $this->rawResponseHeaders);
    $rawHeaders               = array_slice ($rawHeaders, 1, -1);
    $rheaders                 = [];
    $cookies                  = [];

    // Extract headers
    foreach ($rawHeaders as $rh) {
      list ($header, $value) = preg_split ('/:\s*/', $rh, 2);
      if ($header == 'Set-Cookie')
        $cookies[] = preg_replace ('/^([^;]+);.*/', '$1', $value);
      else $rheaders[$header] = $value;
    }

    // Handle cookies
    if (!empty($cookies)) {
      foreach ($cookies as $cookie) {
        list ($name, $val) = explode ('=', $cookie, 2);
        $this->responseCookies[$name] = $val;
      }
      // Prepare cookies header for next request by copying `set-cookie`s from the current response.
      $this->cookieJar = array_merge ($this->cookieJar, $this->responseCookies);
    }

    $this->responseHeaders = $rheaders;
    if (isset($rheaders['Set-Cookie']))
      $this->responseCookies = $rheaders['Set-Cookie'];

    return $response['response'];
  }

  function delete ($url)
  {
    $this->method = 'DELETE';
    $this->url    = func_num_args () == 1 ? $url
      : vsprintf ($url, array_map ('urlencode', array_slice (func_get_args (), 1)));
    return /** HttpRequestInterface */
      $this;
  }

  function expectJson ($assoc = false)
  {
    $this->header ('Accept', 'application/json');
    $this->transform = function ($response) use ($assoc) {
      $this->responseTypeMustBe ('application/json');
      return json_decode ($response, $assoc);
    };
    return /** HttpRequestInterface */
      $this;
  }

  function expectText ()
  {
    $this->header ('Accept', 'text/html,application/xhtml+xml;q=0.9,text/plain;q=0.8,*/*;q=0.1');
    $this->transform = null;
    return /** HttpRequestInterface */
      $this;
  }

  function expectXml ($fullDOM = false, $options = 0)
  {
    $this->header ('Accept', 'application/xml');
    $this->transform = function ($response) use ($fullDOM, $options) {
      $this->responseTypeMustBe ('application/xml');
      if ($fullDOM) {
        $DOM = new DOMDocument;

        return $DOM->loadXML ($response, $options);
      }
      else return new SimpleXMLElement($response);
    };
    return /** HttpRequestInterface */
      $this;
  }

  function get ($url)
  {
    $this->method = 'GET';
    $this->url    = func_num_args () == 1 ? $url
      : vsprintf ($url, array_map ('urlencode', array_slice (func_get_args (), 1)));
    return /** HttpRequestInterface */
      $this;
  }

  function header ($name, $value)
  {
    if (isset($value))
      $this->headers[$name] = $value;
    else unset ($this->headers[$name]);
    return /** HttpRequestInterface */
      $this;
  }

  function headers (array $map)
  {
    foreach ($map as $k => $v)
      $this->header ($k, $v);
    return /** HttpRequestInterface */
      $this;
  }

  function method ($verb)
  {
    $this->method = strtoupper ($verb);
    return /** HttpRequestInterface */
      $this;
  }

  function param ($name, $value)
  {
    if (isset($value))
      $this->params[$name] = is_array ($value) ? array_map ('urlencode', $value) : $value;
    else unset ($this->params[$name]);
    return /** HttpRequestInterface */
      $this;
  }

  function params (array $map)
  {
    foreach ($map as $k => $v)
      $this->param ($k, $v);
    return /** HttpRequestInterface */
      $this;
  }

  function post ($url)
  {
    $this->method = 'POST';
    $this->url    = func_num_args () == 1 ? $url
      : vsprintf ($url, array_map ('urlencode', array_slice (func_get_args (), 1)));
    return /** HttpRequestInterface */
      $this;
  }

  function put ($url)
  {
    $this->method = 'PUT';
    $this->url    = func_num_args () == 1 ? $url
      : vsprintf ($url, array_map ('urlencode', array_slice (func_get_args (), 1)));
    return /** HttpRequestInterface */
      $this;
  }

  /**
   * Gets the response's content type.
   * @return string
   */
  function responseType ()
  {
    return isset($this->responseHeaders['Content-Type']) ? $this->responseHeaders['Content-Type'] : '';
  }

  /**
   * @param string|array $expected Expected content type(s).
   * @throws HttpClientException If no match.
   */
  function responseTypeMustBe ($expected)
  {
    $type = $this->responseType ();
    if (is_array ($expected)) {
      if (in_array ($type, $expected))
        return;
    }
    else if ($type != $expected)
      throw new HttpClientException ("Unexpected content type '$type'");
  }

  function send ()
  {
    $headers = static::headersMapToList ($this->headers);
    $params  = static::paramsToQueryString ($this->params);
    $url     = $this->url . $params;
//    var_dump ($url, $headers);

    $response = $this->call ($url, $this->method, $this->body, $headers);
    if (isset($this->transform)) {
      $fn = $this->transform;
      return $fn ($response);
    }
    else return $response;
  }

  function transform ($callback)
  {
    $this->transform = $callback;
    return /** HttpRequestInterface */
      $this;
  }

  function url ($url)
  {
    $this->url = func_num_args () == 1 ? $url
      : vsprintf ($url, array_map ('urlencode', array_slice (func_get_args (), 1)));
    return /** HttpRequestInterface */
      $this;
  }

  function with ($body, $type = null)
  {
    switch ($type) {
      case 'json':
        $this->header ('Content-Type', 'application/json');
        $this->body = json_encode ($body);
        break;
      case 'form':
        $this->header ('Content-Type', 'application/x-www-form-urlencoded');
        $this->body = http_build_query ($body);
        break;
      case 'text':
        $this->header ('Content-Type', 'text/plain');
        $this->body = $body;
        break;
      case 'xml':
        $this->header ('Content-Type', 'application/xml');
        /** @var \DOMDocument|\SimpleXMLElement $body */
        $this->body = $body instanceof \DOMDocument
          ? $body->saveXML () : ($body instanceof \SimpleXMLElement ? $body->asXML () : $body);
        break;
      case null:
        $this->body = $body;
        break;
      default:
        throw new HttpClientException ("Invalid content type '$type'");
    }
    return /** HttpRequestInterface */
      $this;
  }

}
