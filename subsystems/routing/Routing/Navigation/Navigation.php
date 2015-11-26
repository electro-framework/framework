<?php
namespace Selenia\Routing\Navigation;

use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Traits\InspectionTrait;
use SplObjectStorage;

class Navigation implements NavigationInterface
{
  use InspectionTrait;

  private $ids = [];
  /**
   * @var NavigationLinkInterface
   */
  private $tree;

  function link ()
  {
    $link = new NavigationLink;
    $link->ids =& $this->ids;
    return $link;
  }

  function mount (NavigationLinkInterface $link)
  {
    return $this;
  }

  function buildPath ($url)
  {
    // TODO: Implement buildPath() method.
  }

  function currentPath (SplObjectStorage $path = null)
  {
    // TODO: Implement currentPath() method.
  }

  function getIds ()
  {
    return $this->ids;
  }

  /**
   * Returns a list of the root links for this navigation set.
   * @return NavigationLinkInterface[]
   */
  function getTree ()
  {
    // TODO: Implement getTree() method.
  }
}
