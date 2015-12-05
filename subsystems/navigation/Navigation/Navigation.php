<?php
namespace Selenia\Navigation;

use PhpKit\Flow\Flow;
use Selenia\Exceptions\Fault;
use Selenia\Faults\Faults;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
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

  function buildMenu ()
  {
    foreach ($this->getIterator () as $k => $v) {
      if (!is_string ($k))
        throw new Fault (Faults::MAP_MUST_HAVE_STRING_KEYS);

    }
  }

  function buildPath ($url)
  {
    // TODO: Implement method.
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
    return Flow::from ($this->map)->recursiveUnfold (function (NavigationLinkInterface $link, $key, $depth) {

    })->getIterator ();
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

}
