<?php
namespace Selenia\Routing;

use Exception;
use Selenia\Application;
use Selenia\Exceptions\ConfigException;
use Selenia\Exceptions\FatalException;
use Selenia\Http\Controllers\Controller;
use Selenia\Object;

abstract class AbstractRoute extends Object
{
  const ACTIVE = 'active';
  public $URI;
  public $URIAlias;
  public $URIAlias_regexp;
  public $URI_regexp;
  public $URL;
  public $autoController = false; //autoset if $URI is set
  /**
   * When `true`, the framework will attempt to automatically load the model object by fetching key information from
   * the URL, the route's `preset` property or from the request data.
   * @var bool
   */
  public $autoloadModel = false;
  /**
   * @var Controller
   */
  public $controller; //autoset if $subnavURI is set
  /**
   * Automatically set to true when any sub-navigation links are available under this page.
   * @var bool
   */
  public $hasSubNav = false;    // do not set a default!
  /**
   * CSS class name(s) for menu icon.
   * @var string
   */
  public $icon;
  public $indexTitle;
  public $indexURL;      //autoset if isIndex is set
  /**
   * When set, the real URI is $inheritedPrefix/$URI.
   * @var string
   */
  public $inheritedPrefix = ''; // must not be an empty string.
  public $isIndex         = false;
  /**
   * Indicates if the element matches the current URI.
   * @var boolean
   */
  public $matches = false;
  /**
   * An ordered map of titles to URIs, used to generate menu entries, if set. Otherwise, menu items are generated for
   * the subroutes.
   * @var array|null
   */
  public $menu = null;
  /**
   * The module name for the route.
   * <p>Inherited by all children unless overridden.
   * @var string
   */
  public $module = null;
  public $name;
  public $onMenu; //internal use
  /** @var AbstractRoute */
  public $parent = null;    //internal use
  /**
   * When set, children's inheritedPrefix will be set to inheritedPrefix + prefix.
   * <p>Note that the route's inheritedPrefix is not affected by / related to this.
   * <p>Inherited by all children unless overridden.
   * @var string
   */
  public $prefix = '';    //internal use
  public $routes = null;    //internal use
  /**
   * Indicates if the element is highlighted on the main menu.
   * @var boolean
   */
  public $selected = false; //internal use
  public $subnavURI; //internal use
  public $subnavURL; //internal use
  public $subtitle; //internal use
  public $title;
  /**
   * The type of user that can see this route.
   * @var string
   */
  public $userType;

  public function __construct (array &$init = null)
  {
    parent::__construct ($init);
    if (isset($init))
      $this->preinit ();
  }

  private static function fillURIParam ($match, $data, $ignoreMissing)
  {
    if (isset($data[$match]))
      return $data[$match];
    else if ($ignoreMissing)
      return '';
    throw new Exception($match);
  }

  function activeFor ($condition)
  {
    if (!$condition) {
      $this->onMenu = false;
      $this->URI    = '<unmatchable>';
    }
    return $this;
  }

  public function evalURI ($URIParams = null, $ignoreMissing = true, $URI = null)
  {
    if (is_null ($URIParams))
      $URIParams = $this->getURIParams ();
    if (is_null ($URI))
      $URI = $this->URI;
    try {
      return preg_replace_callback ('!\{(.*?)}!', function ($args) use ($URIParams, $ignoreMissing) {
        return self::fillURIParam ($args[1], $URIParams, $ignoreMissing);
      }, $URI);
    } catch (Exception $e) {
      $x = print_r ($URIParams, true);
      throw new FatalException("URI parameter value for <b>{$e->getMessage()}</b> was not found on the URI parameters:<br><pre>$x<br>URI: <b>$URI</b>");
    }
  }

  public function getIndex ()
  {
    if ($this->isIndex)
      return $this;
    if ($this->parent instanceof AbstractRoute)
      return $this->parent->getIndex ();
    return null;
  }

  public function getPresetParameters ()
  {
    if (!empty($this->preset)) {
      $presetParams = [];
      $paramList    = explode ('&', $this->preset);
      preg_match ('!' . $this->URI_regexp . '!', $_SERVER['REQUEST_URI'], $matches);
      $URIParams = $this->getURIParams ();
      foreach ($paramList as $x) {
        list ($k, $v) = explode ('=', $x);
        if ($v[0] == '{') {
          $i = trim ($v, '{}');
          $v = get ($matches, $i);
          if (!isset($v))
            $v = get ($URIParams, $i);
          if (!isset($v))
            throw new ConfigException("On the preset <b>$this->preset</b>, the key <b>$k</b> was not found on the URI.");
        }
        $presetParams[$k] = $v;
      }
      return $presetParams;
    }
    return null;
  }

  public function getSubtitle ($first = true)
  {
    return $this->getTitle ();
//    if (isset($this->subtitle))
//      return $this->subtitle;
//    return $this->title;
  }

  public function getTitle ()
  {
    if (!empty($this->title))
      return $this->title;
    if (isset($this->controller)) {
      /** @var Controller $ctrl */
      $ctrl              = new $this->controller;
      $ctrl->activeRoute = $this;
      $this->title       = $ctrl->getTitle ();
    }
    return isset($this->title)
      ? $this->title
      : (isset($this->subtitle) ? $this->subtitle
        : (isset($this->parent) ? $this->parent->getTitle () : ''));
  }

  public function getTypes ()
  {
    return [
      'name'           => 'string',
      'title'          => 'string',
      'subtitle'       => 'string',
      'onMenu'         => 'boolean',
      'routes'         => 'array',
      'isIndex'        => 'boolean',
      'indexURL'       => 'string',
      'module'         => 'string',
      'view'           => 'string',
      'controller'     => 'string',
      'autoController' => 'boolean',
      'icon'           => 'string',
    ];
  }

  /**
   * @return array
   * @throws FatalException
   * @global Application $application
   */
  public function getURIParams ()
  {
    global $application;
    $URI    = $application->VURI;
    $result = [];
    $count  = preg_match ("!^$this->URI_regexp(?:$|&|/)!", urldecode ($URI), $URIValues);
    if ($count)
      $uriexp = $this->URI;
    else {
      $count = preg_match ("!^$this->URIAlias_regexp(?:$|&|/)!", urldecode ($URI), $URIValues);
      if ($count)
        $uriexp = $this->URIAlias;
      else $uriexp = '';
    }
    if (preg_match_all ('!\{(.*?)}!', $uriexp, $matches)) {
      foreach ($matches[1] as $i => $field)
        if (count ($URIValues) > $i)
          $result[$field] = get ($URIValues, $i + 1);
        else {
          if ($application->debugMode) {
            $x = "URIValues:\n" . print_r ($URIValues, true);
            $x .= "URIParams:\n" . print_r ($result, true);
            throw new FatalException("No match found for parameter <b>$field</b> on the URI <b>$URI</b> for pattern <b>$uriexp</b><p>URI parameters found:<p><pre>$x");
          }
        }
    }
    return $result;
  }

  public function init ($parent)
  {
    global $application;
    $this->parent = $parent;

    if (!isset ($this->onMenu))
      $this->onMenu = $parent->onMenu;

    if ($this->URI && $this->inheritedPrefix)
      $this->URI = "$this->inheritedPrefix/$this->URI";

    $this->compileURI ();

    if (!isset($this->URL)) {
      if (isset($this->URI))
        $this->URL = "$application->URI/$this->URI";
      else $this->URL = 'javascript:nop()';
    }

    if (isset($this->menu) && $this->inheritedPrefix)
      foreach ($this->menu as $title => &$URI)
        $URI = "$this->inheritedPrefix/$URI";

    if (empty($this->indexURL)) {
      $index = $this->getIndex ();
      if (isset($index)) {
        $this->indexTitle = $index->getSubtitle ();
        $this->indexURL   = $index instanceof RouteGroup ? $index->defaultURI : $index->URL;
        $this->indexURL   = $this->evalURI (null, true, $this->indexURL);
      }
    }
    if (isset($this->routes)) {
      for ($i = 0; $i < count ($this->routes); ++$i) {
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
        if (!isset($route->module))
          $route->module = $this->module;
        $route->inheritedPrefix =
          $this->prefix ? ($this->inheritedPrefix ? "$this->inheritedPrefix/$this->prefix" : $this->prefix)
            : $this->inheritedPrefix;
        /** @var AbstractRoute $route */
        $route->init ($this);
        if ($route->onMenu)
          $this->hasSubNav = true;
      }
    }
  }

  public function preinit ()
  {
    global $application;

    if (!isset($this->subnavURL)) {
      if (isset($this->subnavURI))
        $this->subnavURL = "$application->URI/$this->subnavURI";
      else $this->subnavURL = 'javascript:nop()';
    }
  }

  public function searchFor ($URI, $options = 0)
  {
    if ($this->matchesURI ($URI)) {
      $this->selected = $this->matches = true;
      return $this;
    }
    if (isset($this->routes)) {
      /** @var AbstractRoute $route */
      foreach ($this->routes as $route) {
        $result = $route->searchFor ($URI, $options);
        if (isset($result)) {
          $this->selected = true;
          return $result;
        }
      }
    }
    return null;
  }

  protected function getDefaultSubtitle ()
  {
    return isset($this->parent) ? $this->parent->getDefaultSubtitle () : '';
  }

  protected function matchesMyURI ($URI)
  {
    return preg_match ("!^$this->URI_regexp(?:$|&)!", $URI) > 0;
  }


  protected function matchesMyURIAlias ($URI)
  {
    return preg_match ("!^$this->URIAlias_regexp(?:$|&)!", $URI) > 0;
  }

  protected function matchesURI ($URI)
  {
    return $this->matchesMyURI ($URI) || $this->matchesMyURIAlias ($URI);
  }

  private function compileURI ()
  {
    if (isset($this->URI))
      $this->URI_regexp = preg_replace ('!\{.*?}!', '([^/&]*)', $this->URI);
    else $this->URI_regexp = '<unmatchable>';
    if (isset($this->URIAlias))
      $this->URIAlias_regexp = preg_replace ('!\{.*?}!', '([^/&]*)', $this->URIAlias);
    else $this->URIAlias_regexp = '<unmatchable>';
  }
}
