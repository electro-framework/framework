<?php
namespace Selene\Lib;
use DOMDocument;
use InvalidArgumentException;
use RuntimeException;
use Selene\Contracts\HttpRequestInterface;
use Selene\Exceptions\HttpException;
use SimpleXMLElement;

/**
 * Allows you to make HTTP requests, receive the responses and maintain a session for consecutive requests.
 *
 * This is a high level interface to CURL.
 * > The CURL PHP extension must be available.
 */
class HttpRequest implements HttpRequestInterface
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
   * The current request's HTTP headers.
   *
   * Each array item is a string with the syntax `'name: value'`.
   *
   * > **FOR READING ONLY**
   * > <p>Use {@link Selene\HttpRequest::header()} to modify this property.
   * @var string[]
   */
  public $headers = [];
  /**
   * The current request method.
   * > **FOR READING ONLY**
   * > <p>Use `get(), post(), put()` or `delete()` to modify this property.
   * @var string
   */
  public $method;
  /**
   * The current request's URL parameters.
   *
   * Each array item is a string with the syntax `'name=value'`, where `value` is an URL-encoded string.
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
   * @var string The default value for the `Accept` header for further requests.
   * If unset, no header will be generated, unless manually specified by other means.
   */
  private $accept;
  /**
   * @var string The current cookies as set by the remote host.
   */
  private $requestCookiesHeader;
  /**
   * @var callback A function to transform the response. Receives as argument the raw response text.
   */
  private $transform;

  function __construct ($baseUrl = '')
  {
    $this->baseUrl ($baseUrl);
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

  function accept ($contentType)
  {
    $this->accept = $contentType;
    return /** HttpRequestInterface */
      $this;
  }

  function asJson ($assoc = false)
  {
    $this->accept ('application/json');
    $this->transform = function ($response) use ($assoc) {
      return json_decode ($response, $assoc);
    };
    return /** HttpRequestInterface */
      $this;
  }

  function asText ()
  {
    $this->accept ('text/html,application/xhtml+xml;q=0.9,text/plain;q=0.8,*/*;q=0.1');
    $this->transform = null;
    return /** HttpRequestInterface */
      $this;
  }

  function asXml ($fullDOM = false, $options = 0)
  {
    $this->accept ('application/xml');
    $this->transform = function ($response) use ($fullDOM, $options) {
      if ($fullDOM) {
        $DOM = new DOMDocument;

        return $DOM->loadXML ($response, $options);
      }
      else return new SimpleXMLElement($response);
    };
    return /** HttpRequestInterface */
      $this;
  }

  function baseUrl ($url)
  {
    if ($url && $url[strlen ($url) - 1] != '/')
      $url .= '/';
    $this->baseUrl = $url;
    return /** HttpRequestInterface */
      $this;
  }

  /**
   * Retrieve a web resource from the given URL, optionally sending data.
   *
   * The request is made in the context of the current navigation session.
   * Cookies and referrers are automatically handled.
   *
   * @param string       $url
   * @param string       $method
   * @param array|string $postData
   * @param array        $headers
   * @throws RuntimeException
   * @return string The response body.
   */
  function call ($url, $method = 'GET', $postData = null, $headers = [])
  {
    // Prepare request.

    if (!empty($this->requestCookiesHeader))
      $headers[] = "Cookie: $this->requestCookiesHeader";
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
    $i                        = strpos ($this->rawResponseHeaders, "\n");
    $rawHeaders               = explode ("\n", substr ($this->rawResponseHeaders, $i + 1));
    $rheaders                 = [];
    $cookies                  = [];

    // Extract headers
    foreach ($rawHeaders as $rh) {
      list ($header, $value) = preg_split ('/:\s*/', $rh, 2);
      if ($header == 'Set-Cookie')
        $cookies[] = preg_replace ('/^([^;]+);.*/', '$1', $value);
      else $rheaders[$header] = $value;
    }

    // Assemble cookies
    if (!empty($cookies)) {
      foreach ($cookies as $cookie) {
        list ($name, $val) = explode ('=', $cookie, 2);
        $this->responseCookies[$name] = $val;
      }
      $co = [];

      // Prepare cookies header for next request by copying set-cookies from the current response.

      foreach ($this->responseCookies as $name => $val)
        $co[] = "$name=$val";
      $this->requestCookiesHeader = implode ('; ', $co);
    }

    $this->responseHeaders = $rheaders;
    if (isset($rheaders['Set-Cookie']))
      $this->responseCookies = $rheaders['Set-Cookie'];

    return $response['response'];
  }

  function clearSession ()
  {
    unset ($this->requestCookiesHeader);
    unset ($this->referrer);
    $this->responseCookies = [];
    return /** HttpRequestInterface */
      $this;
  }

  function delete ($url)
  {
    $this->reset ()->method = 'DELETE';
    $this->url              = func_num_args () == 1 ? $url
      : vsprintf ($url, array_map ('urlencode', array_slice (func_get_args (), 1)));
    return /** HttpRequestInterface */
      $this;
  }

  function get ($url)
  {
    $this->reset ()->method = 'GET';
    $this->url              = func_num_args () == 1 ? $url
      : vsprintf ($url, array_map ('urlencode', array_slice (func_get_args (), 1)));
    return /** HttpRequestInterface */
      $this;
  }

  function header ($name, $value)
  {
    $this->headers[] = "$name: $value";
    return /** HttpRequestInterface */
      $this;
  }

  function param ($name, $value)
  {
    if (isset($value)) {
      $value          = urlencode ($value);
      $this->params[] = "$name=$value";
    }
    return /** HttpRequestInterface */
      $this;
  }

  function post ($url)
  {
    $this->reset ()->method = 'POST';
    $this->url              = func_num_args () == 1 ? $url
      : vsprintf ($url, array_map ('urlencode', array_slice (func_get_args (), 1)));
    return /** HttpRequestInterface */
      $this;
  }

  function put ($url)
  {
    $this->reset ()->method = 'PUT';
    $this->url              = func_num_args () == 1 ? $url
      : vsprintf ($url, array_map ('urlencode', array_slice (func_get_args (), 1)));
    return /** HttpRequestInterface */
      $this;
  }

  function send ()
  {
    $headers = $this->headers;
    if (!empty($this->accept))
      $headers[] = "Accept: $this->accept";
    $url = $this->url;
    if (!empty($this->params))
      $url .= '?' . implode ('&', $this->params);
//    var_dump ($url,$headers);

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

  function with ($body)
  {
    $this->body = $body;
    return /** HttpRequestInterface */
      $this;
  }

  private function reset ()
  {
    $this->body      = null;
    $this->headers   = [];
    $this->params    = [];
    $this->transform = null;
    $this->accept    = null;
    return /** HttpRequestInterface */
      $this;
  }
}
