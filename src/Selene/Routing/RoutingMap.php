<?php
namespace Selene\Routing;

use Selene\Exceptions\ConfigException;

class RoutingMap
{
  public $groups;
  public $pages;
  public $routes = [];
  /**
   * A list of references to each named site map node, indexed by name.
   * @var array
   */
  public $namedNodes;
  /** @var bool Reserved for cascading into the children routes. */
  public $onMenu = true;

  public static function loadModule ($moduleName, $configName = 'routes')
  {
    global $application;
    $path = "$application->modulesPath/$moduleName/config/$configName.php";
    if (!fileExists ($path))
      $path = "$application->defaultModulesPath/$moduleName/config/$configName.php";
    $code = file_get_contents ($path, FILE_USE_INCLUDE_PATH);
    if ($code === false)
      throw new ConfigException("RoutingMap::loadConfigOf can't load <b>$configName.php</b> on module <b>$moduleName</b>.");
    $val = evalPHP ($code);
    if ($val === false)
      throw new ConfigException("Error on <b>$moduleName</b>'s routing-map definiton. Please check the PHP code.");
    return $val;
  }

  public function init ()
  {
    //LEGACY
    if (isset($this->groups))
      foreach ($this->groups as $group)
        if (isset($group->routes))
          /** @var AbstractRoute $route */
          foreach ($group->routes as $route)
            $route->init ($group);
    //NEW
    if (isset($this->routes))
      for ($i = 0; $i < count ($this->routes); ++$i) {
        /** @var AbstractRoute $route */
        $route = $this->routes[$i];
        // Flatten subarrays into the base array.
        if (is_array ($route)) {
          array_splice ($this->routes, $i, 1, $route);
          --$i;
          continue;
        }
        // Remove null entries.
        if (is_null ($route)) {
          array_splice ($this->routes, $i, 1);
          --$i;
          continue;
        }
        $route->init ($this);
      }
  }

  /**
   * Returns the AbstractRoute sublass instance that matches the given URI.
   * @param     $URI
   * @param int $options
   * @return AbstractRoute or array(PageRoute,SubPageRoute)
   * @throws ConfigException
   */
  public function searchFor ($URI, $options = 0)
  {
    $URI = ltrim ($URI, '/');
    preg_match ("#\\?\\/?([\\s\\S]*?)(?:&|$)#", $URI, $m);
    if (count ($m) == 2)
      $URI = $m[1];
    //LEGACY
    if (isset($this->groups))
      foreach ($this->groups as $group) {
        /** @var RouteGroup $group */
        $result = $group->searchFor ($URI, $options);
        if (isset($result))
          return $result;
      }
    else if (isset($this->routes))
      foreach ($this->routes as $route) {
        /** @var AbstractRoute $route */
        $result = $route->searchFor ($URI, $options);
        if (isset($result))
          return $result;
      }
    return null;
  }
}
