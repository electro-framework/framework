<?php
namespace Selenia\Routing\Navigation;

use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Traits\InspectionTrait;

class NavigationLink implements NavigationLinkInterface
{
  use InspectionTrait;

  private $enabled = false;
  private $icon;
  private $id;
  /**
   * Note: this will be assigned a reference to an array on a {@see NavigationInterface} instance.
   * @var NavigationLinkInterface[]
   */
  public  $ids;
  private $next    = [];
  private $title;
  private $url;
  private $visible = true;

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
    $this->id = $id;
    return $this->ids[$id] = $this;
  }

  function next (array $next)
  {
    $this->next = $next;
    /** @var NavigationLinkInterface $o */
    foreach ($next as $o)
      array_mergeInto ($this->ids, $o->getIds ());
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
}
