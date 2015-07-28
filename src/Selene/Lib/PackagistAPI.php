<?php
namespace Selene\Lib;
use Selene\Http\HttpClient;
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
    return (new HttpClient)
      ->get ('packages/%s.json', $package)
      ->expectJson ()
      ->send ();
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

    $request = new HttpClient($this->url);
    $request
      ->get ('packages/list.json')
      ->expectJson (true)
      ->params ([
        'type'   => $this->type,
        'vendor' => $this->vendor,
        'tags'   => $this->tags,
      ]);
    $response = $this->response = $request->send ()->packageNames;
    if (!$response)
      throw new \RuntimeException ("$request->method $request->url failed");
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

    $request = new HttpClient($this->url);
    $request
      ->get ('search.json')
      ->expectJson (true)
      ->params ([
        'q'    => $this->query ?: '',
        'type' => $this->type,
        'tags' => $this->tags,
      ]);
    $o          = [];
    $this->page = 0;
    do {
      ++$this->page;
      $request->param ('page', $this->page);
      $response = $this->response = $request->send ();
      if (!$response)
        throw new \RuntimeException ("$request->method $request->url failed");
      $o = array_merge ($o, $response['results']);
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

}
