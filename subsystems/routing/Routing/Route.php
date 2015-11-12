<?php
namespace Selenia\Routing;

use Selenia\Interfaces\RouteInterface;

class Route implements RouteInterface
{
  /** @var string */
  private $location;
  /** @var array */
  private $params;
  /** @var string */
  private $path;
  /** @var string */
  private $prefix;
  /** @var string */
  private $tail;

  public function __construct ($virtualUrlPath, $prevPath = '', array $params = [])
  {
    list ($this->location, $this->tail) = array_merge (explode ('/', $virtualUrlPath, 2), ['']);
    $this->prefix = $prevPath;
    $this->path   = $prevPath ? "$prevPath/$virtualUrlPath" : $this->location;
    $this->params = $params;
  }

  function location ()
  {
    return $this->location;
  }

  function next ()
  {
    return new static ($this->tail, $this->path, $this->params);
  }

  function params ()
  {
    return $this->params;
  }

  function prefix ()
  {
    return $this->prefix;
  }

  function path ()
  {
    return $this->path;
  }

  function remaining ()
  {
    return rtrim ("$this->location/$this->tail", '/');
  }

  function tail ()
  {
    return $this->tail;
  }

  function target ()
  {
    return !strlen ($this->tail);
  }
}
