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
   * @var SplObjectStorage
   */
  private $cachedVisibleTrail;
  /**
   * @var NavigationLinkInterface
   */
  private $currentLink;
  /**
   * @var NavigationLinkInterface
   */
  private $rootLink;

  function __construct ()
  {
    $this->rootLink = $this->group ()->url ('');
  }

  function IDs ()
  {
    return $this->IDs;
  }

  function __debugInfo ()
  {
    $linkToUrl = function (NavigationLinkInterface $link) {
      return $link->rawUrl ();
    };
    return [
      'All IDs<sup>*</sup>'        => PA ($this->IDs)->keys ()->sort ()->join (', '),
      'All URLs<sup>*</sup><br>' .
      '<i>(in scanning order)</i>' => map ($this->rootLink->getDescendants (),
        function ($link) {
          return $link->rawUrl ();
        }),
      'Trail<sup>*</sup>'          => map ($this->getCurrentTrail (), $linkToUrl),
      'Visible trail<sup>*</sup>'  => map ($this->getVisibleTrail (), $linkToUrl),
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

  /**
   * Returns the link that corresponds to the currently visible page.
   * @return NavigationLinkInterface|null null if not found.
   */
  function currentLink ()
  {
    if (!isset($this->cachedTrail)) $this->getCurrentTrail();
    return $this->currentLink;
  }

  function getCurrentTrail ()
  {
    if (isset($this->cachedTrail)) return $this->cachedTrail;
    $url = $this->request ()->getAttribute ('virtualUri');
    $this->currentLink = null;
    $this->buildTrail ($this->rootLink, $trail = new SplObjectStorage, $url);
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

  function getVisibleTrail ()
  {
    if (isset($this->cachedVisibleTrail)) return $this->cachedVisibleTrail;
    $trail  = $this->getCurrentTrail ();
    $vtrail = new SplObjectStorage;
    /** @var NavigationLinkInterface $link */
    foreach ($trail as $link)
      if ($link->isActuallyVisible ())
        $vtrail->attach ($link);
      else break;
    return $this->cachedVisibleTrail = $vtrail;
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

  function offsetExists ($offset)
  {
    return isset($this->IDs[$offset]);
  }

  function offsetGet ($offset)
  {
    return $this->IDs[$offset];
  }

  function offsetSet ($offset, $value)
  {
    throw new Fault (Faults::PROPERTY_IS_READ_ONLY, $offset);
  }

  function offsetUnset ($offset)
  {
    throw new Fault (Faults::PROPERTY_IS_READ_ONLY, $offset);
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

  private function buildTrail (NavigationLinkInterface $link, SplObjectStorage $trail, $url)
  {
    /** @var NavigationLinkInterface $child */
    foreach ($link->links () as $child) {
      if ($child->isActive ()) {
        $trail->attach ($child);
        $this->currentLink = $child;
        $this->buildTrail ($child, $trail, $url);
        // Special case for the home link (URL=='') when the URL to match is not ''
        if ($url !== '' && $child->rawUrl () === '') {
          if ($trail->count () > 1) return;
          // No trail was built, so do not match the home link and proceed to the next root link.
          $trail->detach ($child);
          $this->currentLink = null;
        }
        else return;
      }
    }
  }

}
