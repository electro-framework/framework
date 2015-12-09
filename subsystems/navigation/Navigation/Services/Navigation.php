<?php
namespace Selenia\Navigation\Services;

use Psr\Http\Message\ServerRequestInterface;
use Selenia\Exceptions\Fault;
use Selenia\Faults\Faults;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Navigation\Lib\NavigationLink;
use SplObjectStorage;

/**
 * TODO: allow inserting maps into IDs that have not yet been defined.
 */
class Navigation implements NavigationInterface
{
  /**
   * @var NavigationLinkInterface[]
   */
  private $IDs = [];
  /**
   * @var SplObjectStorage
   */
  private $cachedTrail;
  /**
   * @var NavigationLinkInterface
   */
  private $rootLink;

  public function __construct ()
  {
    $this->rootLink = $this->group ()->url ('');
  }

  function IDs ()
  {
    return $this->IDs;
  }

  function __debugInfo ()
  {
    return [
      'All IDs<sup>*</sup>'        => PA ($this->IDs)->keys ()->sort ()->join (', '),
      'All URLs<sup>*</sup><br>' .
      '<i>(in scanning order)</i>' => map ($this->rootLink->getDescendants (),
        function ($link) {
          return $link->rawUrl ();
        }),
      'Trail<sup>*</sup>'          => map ($this->currentTrail (),
        function (NavigationLinkInterface $link) {
          return $link->rawUrl();
        }),
      'Navigation map<sup>*</sup>' => iterator_to_array ($this->rootLink),
      'request'                    => $this->request (),
    ];
  }

  function add ($navigationMap, $targetId = null)
  {
    if (isset($targetId)) {
      if (!isset($this->IDs[$targetId]))
        throw new Fault (Faults::LINK_NOT_FOUND, $targetId);
      $target = $this->IDs[$targetId];
    }
    else $target = $this->rootLink;
    $target->merge ($navigationMap);
    return $this;
  }

  function currentTrail ()
  {
    if (isset($this->cachedTrail)) return $this->cachedTrail;
    $trail = new SplObjectStorage;
    $link  = $this->rootLink;
    $this->matchChildrenOf ($link, $trail);
    return $this->cachedTrail = $trail;
  }

  function getIterator ()
  {
    return $this->rootLink->getIterator ();
  }

  function getMenu ()
  {
    return $this->rootLink->getMenu ();
  }

  function group ()
  {
    $link        = $this->link ();
    $link->group = true;
    return $link;
  }

  /**
   * Override this if you need to return another type of `NavigationLinkInterface`-compatible instance.
   * @return NavigationLink
   */
  function link ()
  {
    $link      = new NavigationLink;
    $link->IDs =& $this->IDs;
    return $link;
  }

  function request (ServerRequestInterface $request = null)
  {
    if (is_null ($request)) return $this->rootLink->request ();
    $this->rootLink->request ($request);
    return $this;
  }

  function rootLink (NavigationLinkInterface $rootLink = null)
  {
    if (is_null ($rootLink)) return $this->rootLink;
    $this->rootLink = $rootLink;
    return $this;
  }

  private function matchChildrenOf (NavigationLinkInterface $link, SplObjectStorage $trail)
  {
    /** @var NavigationLinkInterface $child */
    foreach ($link->getMenu () as $child) {
      if ($child->isActive ()) {
        $trail->attach ($child);
        $this->matchChildrenOf ($child, $trail);
        return;
      }
    }
  }

}
