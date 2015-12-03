<?php
namespace Selenia\Navigation;

use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Traits\InspectionTrait;

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
   * Note: this is public so that a {@see NavigationInterface} instance can set it when building the navigation.
   * The URL is not available at the time of the link's creation and it will only become available later.
   * @var string
   */
  public $url = '';

  private $enabled              = false;
  private $icon                 = '';
  private $id                   = '';
  private $links                = [];
  private $title                = '';
  private $visible              = true;
  private $visibleIfUnavailable = false;

  function enabled ($enabled = null)
  {
    if (is_null ($enabled)) return $this->enabled;
    $this->enabled = $enabled;
    return $this;
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
    if (isset($this->id))
      throw new \InvalidArgumentException ("Duplicate link ID.");
    $this->id = $id;
    return $this->ids[$id] = $this;
  }

  function isGroup ()
  {
    return $this->group;
  }

  function links ($navigationMap)
  {
    if (!is_iterable($navigationMap))
      throw new \InvalidArgumentException ("The argument must be iterable.");
    $this->links = $navigationMap;
    return $this;
  }

  function title ($title = null)
  {
    if (is_null ($title)) return $this->title;
    $this->title = $title;
    return $this;
  }

  function url ()
  {
    return $this->url;
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
