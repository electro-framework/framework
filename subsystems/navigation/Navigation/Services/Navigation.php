<?php
namespace Selenia\Navigation\Services;

use Psr\Http\Message\ServerRequestInterface;
use Selenia\Exceptions\Fault;
use Selenia\Faults\Faults;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Navigation\Lib\NavigationLink;
use Selenia\Traits\InspectionTrait;
use SplObjectStorage;

/**
 * TODO: optimize navigation maps to be evaluated only on iteration.
 * TODO: allow inserting maps into IDs that have not yet been defined.
 */
class Navigation implements NavigationInterface
{
  use InspectionTrait;

  /**
   * @var NavigationLinkInterface[]
   */
  private $ids = [];
  /**
   * @var NavigationLinkInterface[]
   */
  private $map = [];
  /**
   * @var ServerRequestInterface
   */
  private $request;

  /**
   * Checks if the given argument is a valid iterable value. If it's not, it throws a fault.
   * @param NavigationLinkInterface[]|\Traversable|callable $navMap
   * @return \Iterator
   * @throws Fault {@see Faults::ARG_NOT_ITERABLE}
   */
  static function validateNavMap ($navMap)
  {
    if (!is_iterable ($navMap))
      throw new Fault (Faults::ARG_NOT_ITERABLE);
  }

  function add ($navigationMap)
  {
    self::validateNavMap ($navigationMap);
    array_mergeIterable ($this->map, $navigationMap);
    return $this;
  }

  function buildPath ($url)
  {
    // TODO: Implement method.
  }

  function computeUrls ()
  {
    foreach ($this->map as $k => $l)
      $l->subpath ($k);
  }

  function currentTrail (SplObjectStorage $path = null)
  {
    // TODO: Implement method.
  }

  function getIds ()
  {
    return $this->ids;
  }

  function getIterator ()
  {
    return new \ArrayIterator($this->map);
  }

  /**
   * Returns a list of the root links for this navigation set.
   * @return NavigationLinkInterface[]
   */
  function getTree ()
  {
    // TODO: Implement getTree() method.
  }

  function group ()
  {
    $link        = $this->link ();
    $link->group = true;
    return $link;
  }

  function insertInto ($targetId, $navigationMap)
  {
    if (!isset($this->ids[$targetId]))
      throw new Fault (Faults::LINK_NOT_FOUND, $targetId);
    $this->ids[$targetId]->merge ($navigationMap);
    return $this;
  }

  function link ()
  {
    $link      = new NavigationLink;
    $link->ids =& $this->ids;
    return $link;
  }

  function request (ServerRequestInterface $request)
  {
    if (is_null ($request)) return $this->request;
    $this->request = $request;
    return $this;
  }

}
