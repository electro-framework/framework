<?php
namespace Selenia\Navigation;

use PhpKit\Flow\Flow;
use Selenia\Exceptions\Fault;
use Selenia\Faults\Faults;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Traits\InspectionTrait;
use SplObjectStorage;

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

  function add ($navigationMap)
  {
    if (!is_iterable ($navigationMap))
      throw new \InvalidArgumentException ("The argument must be iterable.");
    array_mergeInto ($this->map, $navigationMap);
    return $this;
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
    return Flow::from ($this->map)->recursiveUnfold (identity ())->getIterator ();
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
    if (!is_iterable ($navigationMap))
      throw new Fault (Faults::ARG_NOT_ITERABLE);
    if (!isset($this->ids[$targetId]))
      throw new Fault (Faults::LINK_NOT_FOUND, $targetId);
    //TODO: array_mergeInto ($this->ids[$targetId]->links, $navigationMap);
    return $this;

  }

  function link ()
  {
    $link      = new NavigationLink;
    $link->ids =& $this->ids;
    return $link;
  }

  function buildMenu ()
  {
    foreach ($this->getIterator () as $k => $v) {
      if (!is_string ($k))
        throw new Fault (Faults::MAP_MUST_HAVE_STRING_KEYS);

    }
  }

}
