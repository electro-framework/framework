<?php
namespace Selenia\Routing;

use Selenia\Interfaces\NavigationInterface;

class Navigation implements NavigationInterface
{
  private $enabled;
  private $icon;
  private $next;
  private $title;
  private $url;
  private $visible;

  function enabled ($enabled = null)
  {
    return isset($enabled) ? $this->enabled = $enabled : $this->enabled;
  }

  function icon ($icon = null)
  {
    return isset($icon) ? $this->icon = $icon : $this->icon;
  }

  function next (array $next)
  {
    return isset($next) ? $this->next = $next : $this->next;
  }

  function title ($title = null)
  {
    return isset($title) ? $this->title = $title : $this->title;
  }

  function url ($url = null)
  {
    return isset($url) ? $this->url = $url : $this->url;
  }

  function visible ($visible = null)
  {
    return isset($visible) ? $this->visible = $visible : $this->visible;
  }

}
