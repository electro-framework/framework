<?php
namespace Selenia\Navigation;

use Selenia\Exceptions\Fault;
use Selenia\Faults\Faults;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Traits\InspectionTrait;

/**
 * TODO: optimize children list to be evaluated only on iteration.
 */
class NavigationLink implements NavigationLinkInterface
{
  use InspectionTrait;

  /**
   * @var bool
   */
  public $group = false;
  /**
   * Note: this will be assigned a reference to an array on a {@see NavigationInterface} instance.
   * @var NavigationLinkInterface[]
   */
  public $ids;
  /**
   * Note: the URL is not available at the time of the link's creation and it will only become available later.
   * <p>Note: this is public so that a {@see NavigationInterface} instance can set it when building the navigation.
   * @var string
   */
  public $url = '';

  private $enabled = false;
  private $icon    = '';
  private $id      = '';
  /** @var NavigationLinkInterface[] */
  private $links                = [];
  /** @var NavigationLinkInterface|NavigationInterface */
  private $parent;
  private $title                = '';
  private $visible              = true;
  private $visibleIfUnavailable = false;

  function enabled ($enabled = null)
  {
    if (is_null ($enabled)) return $this->enabled;
    $this->enabled = $enabled;
    return $this;
  }

  public function getIterator ()
  {
    return array_filter ($this->links, function (NavigationLinkInterface $link, $key) {
      if ($link->isAvailable ()) {
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

  function isAvailable ()
  {
    //TODO: missing functionality
    return $this->enabled;
  }

  function isGroup ()
  {
    return $this->group;
  }

  function links ($navigationMap)
  {
    $this->links = [];
    return $this->merge($navigationMap);
  }

  function merge ($navigationMap)
  {
    Navigation::validateNavMap ($navigationMap);
    /**
     * @var string                  $url
     * @var NavigationLinkInterface $link
     */
    foreach (iterator ($navigationMap) as $url => $link)
      $this->links[$url] = $link->parent ($this);
    return $this;
  }

  function parent ($parent = null)
  {
    if (is_null ($parent)) return $this->parent;
    $this->parent = $parent;
    return $this;
  }

  function title ($title = null)
  {
    if (is_null ($title)) return $this->title;
    $this->title = $title;
    return $this;
  }

  function url ($url = null)
  {
    if (is_null ($url)) return $this->url;
    $this->url = $url;
    return $this;
  }

  function visible ($visible = null)
  {
    if (is_null ($visible)) return $this->visible;
    $this->visible;
    return $this;
  }

  function visibleIfUnavailable ($visible = null)
  {
    if (is_null ($visible)) return $this->visibleIfUnavailable;
    $this->visibleIfUnavailable;
    return $this;
  }

}
