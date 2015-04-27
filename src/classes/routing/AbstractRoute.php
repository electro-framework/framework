<?php

abstract class AbstractRoute extends Object
{
  const ACTIVE = 'active';

  public $name;
  public $title;
  public $subtitle;
  public $URI;
  public $URIAlias;
  public $URL; //autoset if $URI is set
  public $subnavURI;
  public $subnavURL; //autoset if $subnavURI is set
  public $onMenu  = true;
  public $routes  = null;
  public $isIndex = false;
  public $indexURL;      //autoset if isIndex is set
  public $autoView;
  public $autoController;
  /**
   * CSS class name(s) for menu icon.
   * @var string
   */
  public $icon;

  public $parent = null; //internal use
  public $URI_regexp;    //internal use
  public $URIAlias_regexp;    //internal use
  public $indexTitle;    //internal use
  /**
   * Indicates if the element is highlighted on the main menu.
   * @var boolean
   */
  public $selected = false; //internal use
  /**
   * Indicates if the element matches the current URI.
   * @var boolean
   */
  public $matches = false; //internal use
  /**
   * Automatically set to true when any sub-navigation links are available under this page.
   * @var bool
   */
  public $hasSubNav = false;
  /**
   * When set, the real URI is $inheritedPrefix/$URI.
   * @var string
   */
  public $inheritedPrefix = '';
  /**
   * When set, children's inheritedPrefix will be set to its value.
   * @var string
   */
  public $prefix = '';

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

  public function getTypes ()
  {
    return [
      'name'           => 'string',
      'title'          => 'string',
      'subtitle'       => 'string',
      'URI'            => 'string',
      'URIAlias'       => 'string',
      'URL'            => 'string',
      'subnavURI'      => 'string',
      'subnavURL'      => 'string',
      'onMenu'         => 'boolean',
      'routes'         => 'array',
      'isIndex'        => 'boolean',
      'indexURL'       => 'string',
      'autoView'       => 'boolean',
      'autoController' => 'boolean',
      'icon'           => 'string'
    ];
  }

  public function preinit ()
  {
    global $application;

    if (!isset($this->subnavURL)) {
      if (isset($this->subnavURI))
        $this->subnavURL = "$application->URI/$this->subnavURI";
      else $this->subnavURL = 'javascript:nop()';
    }
    if (isset($this->URI))
      $this->URI_regexp = preg_replace ('!\{.*?}!', '([^/&]*)', $this->URI);
    else $this->URI_regexp = '<unmatchable>';
    if (isset($this->URIAlias))
      $this->URIAlias_regexp = preg_replace ('!\{.*?}!', '([^/&]*)', $this->URIAlias);
    else $this->URIAlias_regexp = '<unmatchable>';
  }

  public function getTitle ()
  {
    return isset($this->title)
      ? $this->title
      : (isset($this->subtitle) ? $this->subtitle
        : (isset($this->parent) ? $this->parent->getTitle () : ''));
  }

  public function getSubtitle ($first = true)
  {
    if (isset($this->subtitle))
      return $this->subtitle;
    return $this->title;
  }

  public function init ($parent)
  {
    global $application;

    if (!isset($this->URL)) {
      if (isset($this->URI))
        $this->URL = "$application->URI/" . ($this->inheritedPrefix ? "$this->inheritedPrefix/" : '') . $this->URI;
      else $this->URL = 'javascript:nop()';
    }
    $this->parent = $parent;
    if (empty($this->indexURL)) {
      $index = $this->getIndex ();
      if (isset($index)) {
        $this->indexTitle = $index->getSubtitle ();
        $this->indexURL   = $index->URL;
      }
    }
    if (isset($this->routes)) {
      for ($i = 0; $i < count($this->routes); ++$i) {
        $route = $this->routes[$i];
        // Flatten subarrays into the base array.
        if (is_array ($route)) {
          array_splice ($this->routes,$i,1, $route);
          $route = $this->routes[$i]; // taking into account that the new array may be empty.
        }
        $route->inheritedPrefix = either ($this->prefix, $this->inheritedPrefix);
        /** @var AbstractRoute $route */
        $route->init ($this);
        if ($route->onMenu)
          $this->hasSubNav = true;
      }
    }
  }

  public function searchFor ($URI, $options = 0)
  {
    if ($this->matchesURI ($URI)) {
      $this->selected = $this->matches = true;
      return $this;
    }
    if (isset($this->routes)) {
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

  public function getIndex ()
  {
    if ($this->isIndex)
      return $this;
    if (is_a ($this->parent, 'AbstractRoute'))
      return $this->parent->getIndex ();
    return null;
  }

  /**
   * @return array
   * @throws FatalException
   * @global Application  $application
   * @global ModuleLoader $loader
   */
  public function getURIParams ()
  {
    global $application, $loader;
    $URI    = $this->removeURIPrefix ($loader->virtualURI);
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

  public function evalURI ($URIParams = null, $ignoreMissing = false, $URI = null)
  {
    if (is_null ($URIParams))
      $URIParams = $this->getURIParams ();
    if (is_null ($URI))
      $URI = $this->URI;
    $prefix = empty($this->inheritedPrefix) ? '' : "$this->inheritedPrefix/";
    $URI = "$prefix$URI";
    try {
      return preg_replace_callback ('!\{(.*?)}!', function ($args) use ($URIParams, $ignoreMissing) {
        return self::fillURIParam ($args[1], $URIParams, $ignoreMissing);
      }, $URI);
    } catch (Exception $e) {
      $x = print_r ($URIParams, true);
      throw new FatalException("URI parameter value for <b>{$e->getMessage()}</b> was not found on the URI parameters:<br><pre>$x<br>URI: <b>$URI</b>");
    }
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

  protected function getDefaultSubtitle ()
  {
    return isset($this->parent) ? $this->parent->getDefaultSubtitle () : '';
  }

  protected function removeURIPrefix ($URI)
  {
    if (!empty($this->inheritedPrefix)) {
      $l = strlen ($this->inheritedPrefix);
      if (substr ($URI, 0, $l) == $this->inheritedPrefix)
        $URI = substr ($URI, $l + 1);
    }
    return $URI;
  }

  protected function matchesMyURI ($URI)
  {
    $URI = $this->removeURIPrefix ($URI);
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

}