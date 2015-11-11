<?php
namespace Selenia\Routing;

use Selenia\Interfaces\RouteInterface;

class Route implements RouteInterface
{
  /** @var string */
  private $location;
  /** @var string[] */
  private $locations;
  /** @var array */
  private $params;
  /** @var string */
  private $path;

  public function __construct ($virtualUrlPath, $prevPath = '', array $params = [])
  {
    $this->path      = $prevPath ? "$prevPath/$virtualUrlPath" : $virtualUrlPath;
    $this->locations = explode ('/', $virtualUrlPath);
    $this->location  = array_shift ($this->locations);
    $this->params    = $params;
  }

  function location ()
  {
    return $this->location;
  }

  function next ()
  {
    return new static ($this->tail (), $this->path, $this->params);
  }

  function params ()
  {
    return $this->params;
  }

  function path ()
  {
    return $this->path;
  }

  function tail ()
  {
    return implode ('/', $this->locations);
  }

  function target ()
  {
    return !count ($this->locations);
  }
}
