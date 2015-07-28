<?php
namespace Selene\Lib;
use Selene\Traits\FluentAPI;

/**
 * An interface to packagist.org
 *
 * @method $this query (string $name) Search for packages containing the specified text in the name or description.
 * @method $this tags (string ...$tag) Search for packages containing all the specified tags.
 * @method $this type (string $type) Search for packages of the specified type.
 * @method $this vendor (string $name) Restrict the search to the specified vendor's packages.
 * @method $this page (int $page) Sets the results page number to retrieve, starting from 1.
 */
class PackagistAPI
{
  use FluentAPI;

  /** @var  string */
  protected $url;
  /** @var int */
  private $page = 1;
  /** @var string */
  private $query;
  /** @var \StdClass */
  private $response;
  /** @var string[] */
  private $tags = [];
  /** @var string */
  private $type;
  /** @var string */
  private $vendor;

  function __construct ($packagistUrl = null)
  {
    $this->url = $packagistUrl ?: 'https://packagist.org';
  }

  /**
   * Retrieve full package information.
   *
   * @param string $package Full qualified name ex : myname/mypackage.
   *
   * @return \StdClass A package instance.
   */
  function get ($package)
  {
    return $this->remote (sprintf ('packages/%s.json', $package));
  }

  /**
   * List all packages, with optional filtering.
   *
   * The filters must have been set previously, using JUST ONE of the following setters:
   *
   *    * `vendor()`: vendor of the package
   *    * `type()`:   type of package
   *
   * @return array The results
   */
  function getAll ()
  {
    if ($this->query || $this->tags)
      throw new \RuntimeException ("Invalid filters were specified");

    $response = $this->response =
      $this->remote ('packages/list.json', [
        'type'   => $this->type,
        'vendor' => $this->vendor,
        'tags'   => $this->tags,
      ])
      ['packageNames'];

    return $response;
  }

  /**
   * Are there more pages available?
   * @return boolean
   */
  function hasMore ()
  {
    return isset($this->response) && !isset($this->response['next']);
  }

  /**
   * Search packages.
   *
   * The filters must have been set previouslty, using ONE OR MORE of the following setters:
   *
   *    * `query()`:  search terms
   *    * `type()`:   type of package
   *    * `tags()`:   keywords of the package
   *
   * @param bool $all When `true`, all results will be fetched.
   *                  When `false` (default) a single page (of 15 results) will be fetched (for query searches only).
   * @return array The results
   */
  function search ($all = false)
  {
    if ($this->vendor)
      throw new \RuntimeException ("Invalid filters were specified");

    $o          = [];
    $this->page = 0;
    do {
      ++$this->page;
      $response = $this->response = $this->remote ('search.json', [
        'q'    => $this->query ?: '',
        'type' => $this->type,
        'tags' => $this->tags,
        'page' => $this->page,
      ]);
      $o        = array_merge ($o, $response['results']);
    } while ($all && isset($response['next']));

    return $o;
  }

  /**
   * Total number of pages available.
   * @return int
   */
  function totalPages ()
  {
    return isset($this->response) ? floor (($this->response['total'] - 1) / 15) + 1 : 0;
  }

  /**
   * Total packages found on last search.
   * @return int
   */
  function totalResults ()
  {
    return isset($this->response) ? $this->response['total'] : 0;
  }

  /**
   * A minimalist API for issuing HTTP GET requests to a remote web service.
   *
   * It has no external dependencies.
   *
   * @param string     $url    Web service relative URL.
   * @param array|null $params URL parameteres.
   * @return mixed
   */
  private function remote ($url, array $params = null)
  {
    $url = "$this->url/$url";
    if ($params) $url .= '?' . http_build_query ($params);
    $opts     = [
      'http' =>
        [
          'method'        => 'GET',
          'max_redirects' => 0,
          'ignore_errors' => true,
        ],
    ];
    $context  = stream_context_create ($opts);
    $stream   = fopen ($url, 'r', false, $context);
    $meta     = stream_get_meta_data ($stream);
    $response = stream_get_contents ($stream);
    fclose ($stream);
    $status = $meta['wrapper_data'][0];
    if (!strpos ($status, '200'))
      throw new \RuntimeException (sprintf ("HTTP GET %s failed.%sServer response: %s",
        urldecode ($url), PHP_EOL, $status));
    return json_decode ($response, true);
  }

}
