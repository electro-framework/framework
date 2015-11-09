<?php
namespace Selenia\Routing;

use Selenia\Exceptions\Fatal\ConfigException;
use Selenia\Traits\AssignableTrait;

/**
 * Represents a route segment.
 */
class Location
{
  use AssignableTrait;

  /**
   * The full virtual URL of this location.
   * > **read-only**
   * @var string
   */
  public $path;
  /** @var boolean */
  private $active;
  /** @var boolean */
  private $autocontroller;
  /** @var mixed */
  private $config;
  /** @var string|callable */
  private $controller;
  /** @var string */
  private $menuIcon;
  /** @var bool|callable */
  private $menuItem = false;
  /** @var string|callable */
  private $middleware = [];
  /** @var string */
  private $name;
  /** @var Location[] Maps URL segments (strings) to Location objects. */
  private $next;
  /** @var array maps HTTP verbs to request handlers. Keys are uppercase verbs and values are callables or class names. */
  private $on = [];
  /** @var string */
  private $redirect;
  /** @var boolean */
  private $target;
  /** @var string|callable */
  private $title;
  /** @var string|callable */
  private $view;
  /** @var array */
  private $viewModel;
  /** @var boolean */
  private $waypoint;
  /** @var boolean|callable */
  private $when;

  /**
   * Seets the controller for this location to the predefined autocontroller set on the application's configuration.
   * @param string $enabled
   * @return $this
   */
  function autocontroller ($enabled)
  {
    $this->autocontroller = $enabled;
    return $this;
  }

  /**
   * Attaches custom configuration data to the route, which may be read from a controller or from middleware.
   * @param mixed $config
   * @return $this
   */
  function config ($config)
  {
    $this->config = $config;
    return $this;
  }

  /**
   * Calls a function/method when the last route segment matches this location, irrespective of the request's HTTP verb.
   * > **Note:** Additional verbs specified with `on()` will take precedence over this setting.
   *
   * @param string|callable $controller A callback or the name of a callable class that will be invoked if the route
   *                                    matches this location.
   *                                    <p>The callable target is dependency-injected.
   * @return $this
   */
  function controller ($controller)
  {
    $this->controller = $controller;
    return $this;
  }

  /**
   * Indicates whether the location is part of the currently matched route.
   */
  function isActive ()
  {
    return $this->active;
  }

  /**
   * Indicates whether the location is the last point of the currently matched route.
   */
  function isTarget ()
  {
    return $this->target;
  }

  /**
   * Defines an icon for the corresponding menu item.
   * @param mixed $selectors CSS class list (space-separated)
   * @return $this
   */
  function menuIcon ($selectors)
  {
    $this->menuIcon = $selectors;
    return $this;
  }

  /**
   * Display on main menu?
   * <p>This setting allows the framework to automatically populate the application's main menu.
   * > **Note:** setting a waypoint also sets `menuItem`, as that is the most common case and it saves you from having
   * to also set `menuItem`. If you do not want that, you can disable `menuItem` after enabling `waypoint`.
   *
   * @param boolean|callable $enabled If callable, an injectable function that returns `true` if the location should be
   *                                  displayed on the menu.
   *                                  <p><b>Note:</b> You may inject the current `Location` object.
   * @return $this
   */
  function menuItem ($enabled)
  {
    $this->menuItem = $enabled;
    return $this;
  }

  /**
   * Attaches middleware that will run before any controller.
   * @param array $middleware Array of string|callable.
   *                          <p>String elements are interpreted as class names, which will be
   *                          instantiated (with dependency injection) and __invoke()'d.
   *                          <p>Callables must have a signature conforming to middleware signature.
   * @return $this
   */
  function middleware (array $middleware)
  {
    array_mergeInto ($this->middleware, $middleware);
    return $this;
  }

  /**
   * Assigns a name to the route up to the target segment.
   * <p>Names can be used to generate URLs or to redirect to specific routes.
   * @param string $name
   * @return $this
   */
  function name ($name)
  {
    $this->name = $name;
    return $this;
  }

  /**
   * Defines the next locations for this route.
   * <p>This is a map of location pattern strings to route definitions. Each key is an alternative for matching the URL
   * segment that will be scanned by the router following the current segment.
   *
   * <p>Location patterns have the following syntax:
   * - `'literal'` - matches the literal string; ex: `'users'`
   * - `':param'` - matches any string and assigns it to the router's `UrlParams` map; ex: `':userId'`
   * - `'/regexp/flags:param'` - matches by regular expression and assigns the full matched string to the router's
   * `UrlParams` map; ex: `'/\d+/:userId'`
   * @param Location[] $next
   * @return $this
   */
  function next ($next)
  {
    $this->next = $next;
    return $this;
  }

  /**
   * @param int|string      $verbs      A pipe-delimited list of HTTP verbs.
   *                                    <p>Ex: <kbd>'GET|POST'</kbd>
   *                                    <p>Some valid verbs: <kbd>GET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS</kbd>
   * @param string|callable $controller A callback or the name of a callable class that will be invoked if the route
   *                                    matches this location.
   *                                    <p>String elements are interpreted as class names, which will be
   *                                    instantiated (with dependency injection) and __invoke()'d.
   *                                    <p>Callables must have a signature conforming to middleware signature.
   * @return $this
   * @throws ConfigException
   */
  function on ($verbs, $controller)
  {
    foreach (explode ('|', strtoupper ($verbs)) as $v)
      $this->on[$v] = $controller;
    return $this;
  }

  /**
   * Defines the URL or location name for an automatic redirection if the current URL matches this location.
   * @param string $location It can be an absolute or relative URL, or a location name (prefixed with @).
   * @return $this
   */
  function redirectsTo ($location)
  {
    $this->redirect = $location;
    return $this;
  }

  /**
   * The page's title.
   * <p>By setting the title here, you allow the framework to automatically populate the application's main menu and to
   * also automatically generate navigation breadcrumbs.
   * @param string|callable $title If callable, a function that generates the title dinamically.
   * @return $this
   */
  function title ($title)
  {
    $this->title = $title;
    return $this;
  }

  /**
   * A view template to be rendered when the route matches.
   * <p>This applies only to controllers based on {@see PageController}.
   * <p>When used with `autocontroller(true)`, it allows you to render a page without creating a controller class.
   * @param string|callable $view When a string, specifies a relative path to an external template file; when a
   *                              callable, it should return the rendered view as a string.
   * @return $this
   */
  function view ($view)
  {
    $this->view = $view;
    return $this;
  }

  /**
   * Map of variable names to values to set on the view-model.
   * <p>These variables will be set **in addition** to the ones set on the controller (if any).
   * @param array $vars
   * @return $this
   */
  function viewModel (array $vars)
  {
    $this->viewModel = $vars;
    return $this;
  }

  /**
   * Marks a route location as being a navigation point.
   * <p>When a form is submitted on a page further along the route, the framework can automatically navigate to the
   * nearest waypoint backwards from that location.
   * > **Note:** setting a waypoint also sets `menuItem`, as that is the most common case and it saves you from having
   * to also set `menuItem`. If you do not want that, you can disable `menuItem` after enabling `waypoint`.
   *
   * @param boolean $enabled
   * @return $this
   */
  function waypoint ($enabled)
  {
    if ($this->waypoint = $enabled)
      $this->menuItem = true;
    return $this;
  }

  /**
   * Matches the route only when the argument is `true` or, if a callback is given, if it returns `true`.
   *
   * @param boolean|callable $callback A boolean or an injectable function that returns `boolean`.
   *                                   <p><b>Note:</b> You may inject the current `Location` object.
   * @return $this
   */
  function when ($callback)
  {
    $this->when = $callback;
    return $this;
  }

}
