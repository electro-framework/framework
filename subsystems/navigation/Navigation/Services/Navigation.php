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
   * @var NavigationLinkInterface
   */
  private $rootLink;

  public function __construct ()
  {
    $this->rootLink = $this->group ();
  }

  function IDs ()
  {
    return $this->IDs;
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

  function currentTrail (SplObjectStorage $path = null)
  {
    // TODO: Implement method.
  }

  function getIterator ()
  {
    return $this->rootLink->getIterator ();
  }

  function getMenu ()
  {
    return $this->rootLink->getMenu();
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
    if (is_null ($request)) return $this->rootLink ()->request ();
    $this->rootLink ()->request ($request);
    return $this;
  }

  function rootLink (NavigationLinkInterface $rootLink = null)
  {
    if (is_null ($rootLink)) return $this->rootLink;
    $this->rootLink = $rootLink;
    return $this;
  }

  function __debugInfo ()
  {
    return [
      'IDs*'     => PA ($this->IDs)->keys ()->sort ()->join (', '),
      'URLs*'   => iterator_to_array ($this->getIterator ()),
      'request' => $this->request (),
      'links' => iterator_to_array($this->getMenu())
    ];
  }
}
