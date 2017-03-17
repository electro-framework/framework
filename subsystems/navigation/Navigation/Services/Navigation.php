<?php

namespace Electro\Navigation\Services;

use Electro\Exceptions\Fault;
use Electro\Faults\Faults;
use Electro\Http\Lib\Http;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\Navigation\NavigationLinkInterface;
use Electro\Navigation\Lib\NavigationLink;
use Psr\Http\Message\ServerRequestInterface;

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
   * @var NavigationLinkInterface[]
   */
  private $cachedTrail;
  /**
   * @var NavigationLinkInterface[]
   */
  private $cachedVisibleTrail;
  /**
   * @var NavigationLinkInterface
   */
  private $currentLink;
  /**
   * @var ServerRequestInterface
   */
  private $request;
  /**
   * @var NavigationLinkInterface
   */
  private $rootLink;
  /**
   * @var NavigationLinkInterface
   */
  private $selectedLink;

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
      'Current link'               => $this->currentLink (),
      'All IDs<sup>*</sup>'        => PA ($this->IDs)->keys ()->sort ()->join (', ')->S,
      'All URLs<sup>*</sup><br>' .
      '<i>(in scanning order)</i>' => map ($this->rootLink->getDescendants (),
        function (NavigationLinkInterface $link, &$i) {
          $i = $link->rawUrl ();
          return $link->url ();
        }),
      'Trail<sup>*</sup>'          => map ($this->getCurrentTrail (), $linkToUrl),
      'Visible trail<sup>*</sup>'  => map ($this->getVisibleTrail (), $linkToUrl),
      'Navigation map<sup>*</sup>' => iterator_to_array ($this->rootLink),
      'request'                    => $this->request (),
    ];
  }

  function absoluteUrlOf ($url)
  {
    return Http::absoluteUrlOf ($url, $this->request ());
  }

  function add ($navigationMap, $prepend = false, $targetId = null)
  {
    if (isset($targetId)) {
      if (!isset($this->IDs[$targetId])) {
        inspect ()->simpleTable (array_keys ($this->IDs), 'Registered link IDs');
        throw new Fault (Faults::LINK_NOT_FOUND, $targetId);
      }
      $target = $this->IDs[$targetId];
    }
    else $target = $this->rootLink;
    $target->merge ($navigationMap, $prepend);
    return $this;
  }

  function currentLink ()
  {
    if (!isset($this->cachedTrail)) $this->getCurrentTrail ();
    return $this->currentLink;
  }

  function divider ()
  {
    $link        = $this->link ()->title ('-');
    $link->group = true;
    return $link;
  }

  function getCurrentTrail ($offset = 0)
  {
    return $offset ? array_slice ($this->cachedTrail, $offset) : $this->cachedTrail;
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
    $vtrail = [];
    /** @var NavigationLinkInterface $link */
    foreach ($trail as $link)
      if ($link->isActuallyVisible ())
        $vtrail[] = $link;
    return $this->cachedVisibleTrail = $vtrail;
  }

  function group ()
  {
    $link        = $this->link ();
    $link->group = true;
    return $link;
  }

  function isAbsolute ($url)
  {
    return Http::isAbsoluteUrl ($url);
  }

  /**
   * Override this if you need to return another type of `NavigationLinkInterface`-compatible instance.
   *
   * @return NavigationLink
   */
  function link ()
  {
    $link      = new NavigationLink ($this);
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

  function request ()
  {
    return $this->request;
  }

  function rootLink (NavigationLinkInterface $rootLink = null)
  {
    if (is_null ($rootLink)) return $this->rootLink;
    $this->rootLink = $rootLink;
    return $this;
  }

  function selectedLink ()
  {
    if (!isset($this->cachedTrail)) $this->getCurrentTrail ();
    return $this->selectedLink;
  }

  public function setRequest (ServerRequestInterface $request)
  {
    $this->request = $request;
    if (is_null ($request))
      throw new Fault (Faults::REQUEST_NOT_SET);
    $url               = $request->getAttribute ('virtualUri');
    $this->currentLink = null;
    $this->cachedTrail = [];
    $this->buildTrail ($this->rootLink, $this->cachedTrail, $url);
    if ($this->currentLink) {
      if ($this->currentLink === $this->selectedLink)
        $this->currentLink->setState (true, true, true);
      else {
        $this->currentLink->setState (true, false, true);
        if ($this->selectedLink)
          $this->selectedLink->setState (true, true, false);
      }
    }
  }

  private function buildTrail (NavigationLinkInterface $link, array & $trail, $url)
  {
    /** @var NavigationLinkInterface $child */
    foreach ($link->links () as $child) {
      if ($this->linkIsActive ($child, $url)) {
        $trail[]           = $child;
        $this->currentLink = $child;
        if ($child->isActuallyVisible ())
          $this->selectedLink = $child;
        $child->setState (true, false, false);
        $this->buildTrail ($child, $trail, $url);
        return;
      }
    }
  }

  private function linkIsActive (NavigationLinkInterface $link, $url)
  {
    $linkUrl = $link->url ();
    if ($linkUrl === $url) return true;
    foreach ($link->links () as $sub)
      if ($this->linkIsActive ($sub, $url)) return true;
    return false;
  }

}
