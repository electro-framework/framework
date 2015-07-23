<?php
namespace Selene\Lib;
use DOMDocument;
use InvalidArgumentException;
use RuntimeException;
use Selene\Contracts\HttpRequestInterface;
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
  /**
   * The request payload for POST or PUT requests.
   * @var string
   */
  public $body;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @var string The current cookies as set by the remote host.
   */
  private $requestCookiesHeader;

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

  function baseUrl ($url)
  {
    if ($url && $url[strlen ($url) - 1] != '/')
      $url .= '/';
    $this->baseUrl = $url;
    /** @var HttpRequestInterface $self */
    $self = $this; // Prevent IDE from inferring $this as the return type.
    return $self;
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
      throw new RuntimeException($err, $this->responseStatus);
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

  function clear ()
  {
    unset ($this->requestCookiesHeader);
    unset ($this->referrer);
    $this->responseCookies = [];
    /** @var HttpRequestInterface $self */
    $self = $this; // Prevent IDE from inferring $this as the return type.
    return $self;
  }

  function delete ($url)
  {
    $this->method = 'DELETE';
    $this->url    = $url;
    /** @var HttpRequestInterface $self */
    $self = $this; // Prevent IDE from inferring $this as the return type.
    return $self;
  }


  function get ($url)
  {
    $this->method = 'GET';
    $this->url    = $url;
    /** @var HttpRequestInterface $self */
    $self = $this; // Prevent IDE from inferring $this as the return type.
    return $self;
  }

  function send ($body) {
    $this->body = $body;
    return $this;
  }

  function go ()
  {
    return $this->call ($this->url, $this->method, $this->body, $this->headers);
  }

  function asText () {
    return $this->header('Accept', 'text/html,application/xhtml+xml;q=0.9,text/plain;q=0.8,*/*;q=0.1')->go();
  }

  function asXml ($fullDOM = false, $options = 0) {
    $r = $this->header('Accept', 'application/xml')->go();
    if ($fullDOM) {
      $DOM = new DOMDocument;
      return $DOM->loadXML ($r, $options);
    }
    else return new SimpleXMLElement($r);
  }

  function asJson ($associative = false) {
    $r = $this->header('Accept', 'application/json')->go();
    return json_decode($r, $associative);
  }

  function header ($name, $value)
  {
    $this->headers[] = "$name: $value";
    /** @var HttpRequestInterface $self */
    $self = $this; // Prevent IDE from inferring $this as the return type.
    return $self;
  }

  function param ($name, $value)
  {
    if (isset($value)) {
      $value          = urlencode ($value);
      $this->params[] = "$name=$value";
    }
    /** @var HttpRequestInterface $self */
    $self = $this; // Prevent IDE from inferring $this as the return type.
    return $self;
  }

  function post ($url)
  {
    $this->method = 'POST';
    $this->url    = $url;
    /** @var HttpRequestInterface $self */
    $self = $this; // Prevent IDE from inferring $this as the return type.
    return $self;
  }

  function put ($url)
  {
    $this->method = 'PUT';
    $this->url    = $url;
    /** @var HttpRequestInterface $self */
    $self = $this; // Prevent IDE from inferring $this as the return type.
    return $self;
  }
}
