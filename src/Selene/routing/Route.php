<?php
namespace Selene\Routing;

use Selene\TFluentInterface;

class Route
{
  use TFluentInterface;

  /**
   *
   * @var string
   */
  public $URI;
  /**
   * @var string Regular expression match.
   */
  public $match;
  /**
   * # A map of HTTP verbs to handler callbacks.
   *
   *  Ex: `route()->on (['get' => public $() { return 0; }])`
   * @var array
   */
  public $on;
  /**
   * URI prefix to be added to all sub-routes.
   * @var string
   */
  public $prefix;
  /**
   * The module name (vendor/module) to be loaded.
   */
  public $module;
  /**
   * A map of one or more module Settings that can be accessed by name.
   * @var string
   */
  public $config;
  /**
   *
   * @var IRoute[]
   */
  public $routes = [];
  /**
   * @var array
   */
  public $middleware = [];

  /**
   * Causes the specified module to be loaded.
   * @var string $module The module name (vendor/module).
   * @var array  $config A map of one or more Settings that can be accessed by name.
   * @return $this
   */
  function module ($module, array $config = null)
  {
    $this->module = $module;
    $this->config = $config;
    return $this;
  }
}