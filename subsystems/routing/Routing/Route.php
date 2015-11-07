<?php
namespace Selenia\Routing;

use Selenia\Traits\ConfigurationTrait;

/**
 * Represents a route segment.
 *
 * @method $this|string for (string $v = null) Match a constant URL segment. Ex: <kbd>match ('users')</kbd>
 * @method $this|string param (string $v = null) Match a variable URL segment. Ex: <kbd>param ('userId')</kbd>
 * @method $this|string match (string|int $v = null) Sets a reg.exp. for param. matching. Ex: <kbd>match ('\d+')</kbd>
 * @method $this|callable if (callable $v = null) Matches the route only when the callback returns true.
 * @method $this|callable onStop (callable $v = null) Calls a function/method when the last route segment matches.
 * @method $this|callable onRoute (callable $v = null) Calls a function/method when the route segment matches.
 * @method $this|boolean onMenu (boolean $v = null) Display on main menu?
 * @method $this|callable onMenuIf (callable $v = null) Display on main menu if the callback returns true.
 * @method $this|array config (array $v = null) Attaches additional configuration data to the route.
 * @method $this|array middleware (array $v = null) Attaches middleware that will run before do()/controller().
 * @method $this|string icon (string $v = null) A CSS class list that defines an icon for the route on the main menu.
 * @method $this|string title (string $v = null) The page's title.
 * @method $this|callable dynamicTtle (callable $v = null) A callback that gemerates the page's title.
 * @method $this|string name (string $v = null) Assigns a name to the route up to the target segment.
 * @method $this|array routes (array $v = null) A list of the next route segments.
 * @method $this|string render (string $v = null) A view template to be rendered when the route matches.
 * @method $this|boolean waypoint (boolean $v = null) Marks the route segment as a waypoint (for stopping  on reverse).
 * @method $this|array vars (array $v = null) A map of variable names to values that will be set on the view-model.
 */
class Route
{
  use ConfigurationTrait;

  private $config;
  private $dynamicTitle;
  private $for;
  private $icon;
  private $if;
  private $match;
  private $middleware;
  private $name;
  private $onMenu = false;
  private $onMenuIf;
  private $onRoute;
  private $onStop;
  private $param;
  private $render;
  private $routes;
  private $title;
  private $vars;
  private $waypoint;

  function __construct ()
  {
//    $this->
  }

  function isActive ()
  {

  }

  function isMatch ()
  {

  }

}
