<?php

class RouteGroup extends AbstractRoute
{
  public $defaultURI;
  public $baseSubnavURI;
  public $includeMainItemOnNav = false;

  public function __construct (array &$init = null)
  {
    parent::__construct ($init);
  }

  public function getTypes ()
  {
    return array_merge (parent::getTypes (), [
      'defaultURI'           => 'string',
      'baseSubnavURI'        => 'string',
      'includeMainItemOnNav' => 'boolean'
    ]);
  }

  public function preinit ()
  {
    parent::preinit ();
    if (isset($this->baseSubnavURI))
      $this->baseSubnavURI = str_replace ('*', '[^/]*', $this->baseSubnavURI);
  }

  public function searchFor ($URI, $options = 0)
  {
    if ($this->matchesURI ($URI)) {
      $this->selected = true;
      if ($options & SiteMapSearchOptions::INCLUDE_GROUPS)
        return $this;
    }
    if (isset($this->routes))
      foreach ($this->routes as $route) {
        $result = $route->searchFor ($URI, $key = null);
        if (isset($result)) {
          $this->selected = true;
          return $result;
        }
      }
    return null;
  }
}
