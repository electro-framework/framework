<?php
namespace Selenia\Navigation\Lib;

use Psr\Http\Message\ServerRequestInterface;
use Selenia\Exceptions\Fault;
use Selenia\Faults\Faults;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Navigation\Services\Navigation;
use Selenia\Traits\InspectionTrait;

/**
 * TODO: optimize children list to be evaluated only on iteration.
 */
class NavigationLink implements NavigationLinkInterface
{
  use InspectionTrait;

  /**
   * Note: this is accessible to `Navigation`.
   * @var bool
   */
  public $group = false;
  /**
   * Note: this will be assigned a reference to an array on a {@see NavigationInterface} instance.
   * @var NavigationLinkInterface[]
   */
  public $ids;
  /** @var string */
  private $actualUrl = '';
  /** @var bool|callable */
  private $enabled = false;
  /** @var string */
  private $icon = '';
  /** @var string */
  private $id = '';
  /** @var NavigationLinkInterface[] */
  private $links = [];
  /** @var NavigationLinkInterface|NavigationInterface */
  private $parent;
  /** @var string|null */
  private $subpath = null;
  /** @var string|callable */
  private $title = '';
  /** @var string|callable */
  private $url = '';
  /** @var bool|callable */
  private $visible = true;
  /** @var bool */
  private $visibleIfUnavailable = false;

  function actualUrl ()
  {

  }

  function enabled ($enabled = null)
  {
    if (is_null ($enabled))
      return is_callable ($enabled = $this->enabled) ? $enabled() : $enabled;
    $this->enabled = $enabled;
    return $this;
  }

  public function getIterator ()
  {
    $request = $this->getRequest ();
    return array_filter ($this->links, function (NavigationLinkInterface $link, $key) use ($request) {
      $url = is_int ($key) ? $link->url () : $key;
      if ($url && $url[0] == '@') {
        if (is_null ($url = $request->getAttribute ($url)))
          return $link->visibleIfUnavailable () && $link->visible ();
      }

      if ($link->isActuallyEnabled ()) {
        if (is_int ($key)) ; //...
        return true;
      }
      return false;
    }, ARRAY_FILTER_USE_BOTH);
  }

  function icon ($icon = null)
  {
    if (is_null ($icon)) return $this->icon;
    $this->icon = $icon;
    return $this;
  }

  function id ($id = null)
  {
    if (is_null ($id)) return $this->id;
    if (isset($this->ids[$id]))
      throw new Fault (Faults::DUPLICATE_LINK_ID, $id);
    $this->id = $id;
    return $this->ids[$id] = $this;
  }

  function isActuallyEnabled ()
  {
    if (isset($this->url) && $this->enabled ()) {
      return $this->url && $this->url[0] == '@'
        ? !is_null ($this->getRequest ()->getAttribute ($this->url))
        : true;
    }
    return false;
  }

  function isActuallyVisible ()
  {
    if (isset($this->url) && $this->visible ()) {
      return $this->url && $this->url[0] == '@'
        ? !is_null ($this->getRequest ()->getAttribute ($this->url))
        : true;
    }
    return false;
  }

  function isGroup ()
  {
    return $this->group;
  }

  function links ($navigationMap)
  {
    $this->links = [];
    return $this->merge ($navigationMap);
  }

  function merge ($navigationMap)
  {
    Navigation::validateNavMap ($navigationMap);
    /**
     * @var string                  $key
     * @var NavigationLinkInterface $link
     */
    foreach (iterator ($navigationMap) as $key => $link) {
      $this->links[$key] = $link->parent ($this);
      if (is_string ($key)) $link->url ($key);
    }
    return $this;
  }

  function parent ($parent = null)
  {
    if (is_null ($parent)) return $this->parent;
    $this->parent = $parent;
    return $this;
  }

  function subpath ($subpath = null)
  {
    if (is_null ($subpath)) return $this->subpath;

    if (is_int ($subpath))
      $subpath = null;
    elseif ($subpath != '' && $subpath[0] == '@')
      $subpath = $this->getRequest ()->getAttribute ($subpath);
    $this->subpath = $subpath;

    $url = $this->url ();
    if (!exists ($url)) $url = $subpath;

    // If not an full/absolute URL, concatenate the URL to the parent's URL.
    if (!str_beginsWith ('http', $url) && !($url != '' && $url[0] == '/')) {
      $parent = $this->parent;
      $base   = $parent instanceof NavigationInterface ? $parent->actualUrl () : '';
      $url    = exists ($base) ? "$base/$url" : $url;
    }
    $this->actualUrl = $url;

    // Propagate the URL generation to the children only if the current link has an URL.
    if (isset($url))
      foreach ($this->links as $key => $link)
        $link->subpath ($key);

    return $this;
  }

  function title ($title = null)
  {
    if (is_null ($title))
      return is_callable ($title = $this->title) ? $title() : $title;
    $this->title = $title;
    return $this;
  }

  function url ($url = null)
  {
    if (is_null ($url))
      return is_callable ($url = $this->url) ? $url() : $url;
    $this->url = $url;
    return $this;
  }

  function visible ($visible = null)
  {
    if (is_null ($visible))
      return is_callable ($visible = $this->visible) ? $visible() : $visible;
    $this->visible = $visible;
    return $this;
  }

  function visibleIfUnavailable ($visible = null)
  {
    if (is_null ($visible)) return $this->visibleIfUnavailable;
    $this->visibleIfUnavailable = $visible;
    return $this;
  }

  /**
   * @return ServerRequestInterface
   * @throws Fault
   */
  private function getRequest ()
  {
    if (!$this->parent || !($request = $this->parent->request ()))
      throw new Fault (Faults::REQUEST_NOT_SET);
    return $request;
  }

}
