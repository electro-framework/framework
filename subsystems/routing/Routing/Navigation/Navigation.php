<?php
namespace Selenia\Routing\Navigation;

use PhpKit\Flow\Flow;
use Selenia\Exceptions\Fatal\ConfigException;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Traits\InspectionTrait;
use SplObjectStorage;

class Navigation implements NavigationInterface
{
  use InspectionTrait;

  private $ids = [];
  /**
   * @var NavigationLinkInterface[]
   */
  private $map = [];
  /**
   * Array of iterables. An iterable, on this context, is {@see NavigationLinkInterface}[] | {@see \Traversable} |
   * `callable`.
   * @var array
   */
  private $maps = [];

  function add ($navigationMap)
  {
    if (!is_iterable ($navigationMap))
      throw new \InvalidArgumentException ("The argument must be iterable.");
    $this->maps[] = $navigationMap;
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
    return Flow::from ($this->maps)->recursiveUnfold (identity ())->getIterator ();
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
        throw new ConfigException ("Navigation maps must only contain string keys.");

    }
  }

}
