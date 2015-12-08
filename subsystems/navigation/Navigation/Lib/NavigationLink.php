<?php
namespace Selenia\Navigation\Lib;

use PhpKit\Flow\Flow;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Exceptions\Fault;
use Selenia\Faults\Faults;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Traits\InspectionTrait;

/**
 * TODO: optimize children list to be evaluated only on iteration.
 */
class NavigationLink implements NavigationLinkInterface
{
  use InspectionTrait;

  const NOT_AVAILABLE_URL = '@';

  /**
   * Note: this will be assigned a reference to an array on a {@see NavigationInterface} instance.
   * @var NavigationLinkInterface[]
   */
  public $IDs;
  /**
   * Note: this is accessible to `Navigation`.
   * @var bool
   */
  public $group = false;
  /** @var string|null When set, `url()` will always return its value. */
  private $cachedUrl = null;
  /** @var bool|callable */
  private $enabled = true;
  /** @var string */
  private $icon = '';
  /** @var string */
  private $id = '';
  /** @var NavigationLinkInterface[] */
  private $links = [];
  /** @var NavigationLinkInterface */
  private $parent;
  /** @var ServerRequestInterface */
  private $request;
  /** @var string|callable */
  private $title = '';
  /** @var string|callable|null When null, the value will be computed on demand */
  private $url = null;
  /** @var bool|callable */
  private $visible = true;
  /** @var bool */
  private $visibleIfUnavailable = false;

  /**
   * Checks if the given argument is a valid iterable value. If it's not, it throws a fault.
   * @param NavigationLinkInterface[]|\Traversable|callable $navMap
   * @return \Iterator
   * @throws Fault {@see Faults::ARG_NOT_ITERABLE}
   */
  static function validateNavMap ($navMap)
  {
    if (!is_iterable ($navMap))
      throw new Fault (Faults::ARG_NOT_ITERABLE);
  }

  function enabled ($enabled = null)
  {
    if (is_null ($enabled))
      return is_callable ($enabled = $this->enabled) ? $enabled() : $enabled;
    $this->enabled = $enabled;
    return $this;
  }

  public function getIterator ()
  {
    $x = Flow::from ($this->links)->recursiveUnfold (function ($v, $k, $depth) {
      if (is_string ($v)) return $v;
      if ($v instanceof NavigationLinkInterface) return iterator ([$v->url (), $v->getIterator ()]);
      return $v;
    });
    return $x;
  }

  function getMenu ()
  {
    $request = $this->getRequest ();
    return Flow::from ($this->links)->where (function (NavigationLinkInterface $link, $key) use ($request) {
      $url = $link->url ();
      if ($url && $url[0] == '@') {
        if (is_null ($url = $request->getAttribute ($url)))
          return $link->visibleIfUnavailable () && $link->visible ();
      }

      if ($link->isActuallyEnabled ()) {
        if (is_int ($key)) ; //...
        return true;
      }
      return false;
    })->reindex ()->getIterator ();
  }

  function icon ($icon = null)
  {
    if (is_null ($icon)) return $this->icon;
    $this->icon = $icon;
    return $this;
  }

  function id ($id = null)
  {
    if (is_null ($id)) return $this->id;
    if (isset($this->IDs[$id]))
      throw new Fault (Faults::DUPLICATE_LINK_ID, $id);
    $this->id = $id;
    return $this->IDs[$id] = $this;
  }

  function isActuallyEnabled ()
  {
    $url = $this->url ();
    return isset($url) && $this->enabled () && $url != self::NOT_AVAILABLE_URL;
  }

  function isActuallyVisible ()
  {
    $url = $this->url;
    if (isset($url) && $this->visible ()) {
      return $url && $url[0] == '@'
        ? !is_null ($this->getRequest ()->getAttribute ($url))
        : true;
    }
    return false;
  }

  function isGroup ()
  {
    return $this->group;
  }

  function links ($navigationMap = null)
  {
    if (is_null ($navigationMap)) return $this->links;
    $this->links = [];
    return $this->merge ($navigationMap);
  }

  function merge ($navigationMap)
  {
    self::validateNavMap ($navigationMap);
    /**
     * @var string                  $key
     * @var NavigationLinkInterface $link
     */
    foreach (iterator ($navigationMap) as $key => $link) {
      $this->links[$key] = $link->parent ($this); // assigns $link and sets its parent
      if (is_string ($key)) $link->url ($key);
    }
    return $this;
  }

  function parent (NavigationLinkInterface $parent = null)
  {
    if (is_null ($parent)) return $this->parent;
    $this->parent = $parent;
    return $this;
  }

  function request (ServerRequestInterface $request = null)
  {
    if (is_null ($request))
      return $this->request ?: $this->request = ($this->parent ? $this->parent->request () : null);
    $this->request = $request;
    return $this;
  }

  function title ($title = null)
  {
    if (is_null ($title))
      return is_callable ($title = $this->title) ? $title() : $title;
    $this->title = $title;
    return $this;
  }

  function url ($url = null)
  {
    if (is_null ($url)) {
      if (isset($this->cachedUrl))
        return $this->cachedUrl;
      if (is_callable ($url = $this->url))
        $url = $url();
      if (!str_beginsWith ('http', $url) && !($url != '' && $url[0] == '/'))
        $url = enum ('/', $this->parent->url (), $url);
      $url = $this->evaluateUrl ($url);
      return $this->cachedUrl = $url;
    }
    $this->url = $url;
    return $this;
  }

  function visible ($visible = null)
  {
    if (is_null ($visible))
      return is_callable ($visible = $this->visible) ? $visible() : $visible;
    $this->visible = $visible;
    return $this;
  }

  function visibleIfUnavailable ($visible = null)
  {
    if (is_null ($visible)) return $this->visibleIfUnavailable;
    $this->visibleIfUnavailable = $visible;
    return $this;
  }

  private function evaluateUrl ($url)
  {
    $request = $this->getRequest ();
    $available = true;
    $url = preg_replace_callback ('/@\w+/', function ($m) use ($request, &$available) {
      $v = $request->getAttribute ($m[0]);
      if (is_null ($v)) {
        $available = false;
        return '';
      }
      return $v;
    }, $url);
    return $available ? $url : self::NOT_AVAILABLE_URL;
  }

  /**
   * @return ServerRequestInterface
   * @throws Fault
   */
  private function getRequest ()
  {
    $request = $this->request ();
    if (!$request)
      throw new Fault (Faults::REQUEST_NOT_SET);
    return $request;
  }

}
