<?php
namespace Selene\Lib;
use Selene\Traits\FluentAPI;

/**
 * An interface to packagist.org
 *
 * @method $this name (string $name) Search for packages containing the specified string in the packages's name.
 * @method $this tags (string ...$tag) Search for packages containing all the specified tags.
 * @method $this type (string $type) Search for packages of the specified type.
 * @method $this vendor (string $name) Restrict the search to the specified vendor's packages.
 * @method $this page (int $page) Get the results page with the specified ordinal index [1..]
 */
class PackagistAPI
{
  use FluentAPI;

  /** @var  string */
  protected $url;
  /** @var string */
  private $name;
  /** @var string[] */
  private $tags = [];
  /** @var string */
  private $type;
  /** @var string */
  private $vendor;
  /** @var int */
  private $page = 1;

  function __construct ($packagistUrl = null)
  {
    $this->url = $packagistUrl ?: 'https://packagist.org';
  }

  function search ()
  {
    $request = new HttpRequest($this->url);
    $request
      ->get ('search.json')
      ->param ('q', $this->name ?: '')
      ->param ('type', $this->type)
      ->param ('vendor', $this->vendor);
    foreach ($this->tags as $tag)
      $request->param ('tags[]', $tag);
    return $request
      ->param ('page', $this->page)
      ->asJson();
  }

}
