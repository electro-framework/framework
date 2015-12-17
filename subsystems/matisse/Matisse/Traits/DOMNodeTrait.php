<?php
namespace Selenia\Matisse\Traits;

use Selenia\Matisse\Attributes\ComponentAttributes;
use Selenia\Matisse\Component;
use Selenia\Matisse\ComponentInspector;
use Selenia\Matisse\Components\Page;
use Selenia\Matisse\Components\Parameter;
use Selenia\Matisse\Exceptions\ComponentException;

/**
 * Provides an API for manipulating DOM nodes on a tree of components.
 *
 * It's applicable to the Component class.
 *
 * @property Page $page
 * @property ComponentAttributes $attrsObj
 */
trait DOMNodeTrait
{
  /**
   * Can this component have children?
   * @var bool
   */
  public $allowsChildren = false;
  /**
   * An array of child components that are either defined on the source code or
   * generated dinamically.
   *
   * <p>This can never be `null`.
   * <p>**READONLY** - Never set this directly.
   *
   * @var Component[]
   */
  private $children = [];
  /**
   * Points to the parent component in the page hierarchy.
   * It is set to NULL if the component is the top one (a Page instance) or if it's standalone.
   *
   * @var Component|null
   */
  public $parent = null;

  /**
   * @param Component[] $components
   * @param Component   $parent
   * @return Component[]|null
   */
  public static function cloneComponents (array $components = null, Component $parent = null)
  {
    if (isset($components)) {
      $result = [];
      foreach ($components as $component) {
        /** @var Component $cloned */
        $cloned = clone $component;
        if (isset($parent))
          $cloned->attachTo ($parent);
        else $cloned->detach ();
        $result[] = $cloned;
      }

      return $result;
    }

    return null;
  }

  /**
   * @param Component[]|null $components
   */
  static public function detachAll (array $components)
  {
    foreach ($components as $child)
      $child->detach ();
  }

  public function __clone ()
  {
    if (isset($this->attrsObj)) {
      $this->attrsObj = clone $this->attrsObj;
      $this->attrsObj->setComponent ($this);
    }
    if ($this->children)
      $this->children = self::cloneComponents ($this->children, $this);
  }

  public function addChild (Component $child)
  {
    if ($child) {
      $this->children[] = $child;
      $this->attach ($child);
    }
  }

  public function addChildren (array $children = null)
  {
    if ($children) {
      array_mergeInto ($this->children, $children);
      $this->attach ($children);
    }
  }

  /**
   * @param Component|Component[] $childOrChildren
   */
  public function attach ($childOrChildren = null)
  {
    if (!empty($childOrChildren)) {
      if (is_array ($childOrChildren))
        foreach ($childOrChildren as $child)
          /** @var Component $child */
          $child->attachTo ($this);
      else $childOrChildren->attachTo ($this);
    }
  }

  public function attachTo (Component $parent = null)
  {
    $this->parent = $parent;
    $this->setPage ($parent->page);
  }

  public function detach ()
  {
    $this->parent = null;
    // The page remains constant.
    // $this->setPage (null);
  }

  /**
   * @param string|null $attrName [optional] An attribute name. If none, returns all the component's children.
   * @return Component[] Never null.
   * @throws ComponentException If the specified attribute is not a parameter.
   */
  public function getChildren ($attrName = null)
  {
    if (is_null ($attrName))
      return $this->children;

    if (isset($this->attrsObj->$attrName)) {
      $p = $this->attrsObj->$attrName;
      if ($p instanceof Parameter)
        return $p->children;
      throw new ComponentException($this,
        "Can' get children of attribute <b>$attrName</b>, which has a value of type <b>" . gettype ($p) . '</b>.');
    }
    return [];
  }

  public function setChildren (array $children = [], $attach = true)
  {
    if ($this->children)
      $this->removeChildren ();
    $this->children = $children;
    if ($attach)
      $this->attach ($children);
  }

  public function getClonedChildren ($attrName = null)
  {
    return self::cloneComponents ($this->getChildren ($attrName));
  }

  /**
   * Returns the ordinal index of this component on the parent's child list.
   *
   * @return int|boolean
   * @throws ComponentException
   */
  public function getIndex ()
  {
    if (!isset($this->parent))
      throw new ComponentException($this, "The component is not attached to a parent.");
    if (!$this->parent->children)
      throw new ComponentException($this, "The parent component has no children.");

    return array_search ($this, $this->parent->children, true);
  }

  /**
   * Returns the ordinal index of the specified child on this component's child list.
   *
   * @param Component $child
   * @return bool|int
   */
  public function indexOf (Component $child)
  {
    return array_search ($child, $this->children, true);
  }

  public function remove ()
  {
    if (isset($this->parent))
      $this->parent->removeChild ($this);
  }

  public function removeChild (Component $child)
  {
    $p = $this->indexOf ($child);
    if ($p === false)
      throw new ComponentException($child,
        "The component is not a child of the specified parent, so it cannot be removed.");
    array_splice ($this->children, $p, 1);
    $child->detach ();
  }

  /**
   * Removes, detaches and returns the component's children.
   */
  public function removeChildren ()
  {
    $children       = $this->children;
    $this->children = [];
    self::detachAll ($children);
    return $children;
  }

  /**
   * Replaces the component by the specified componentes in the parent's child list.
   * The component itself is discarded from the components tree.
   *
   * @param array $components
   * @throws ComponentException
   */
  public function replaceBy (array $components = null)
  {
    $p = $this->getIndex ();
    if ($p !== false) {
      array_splice ($this->parent->children, $p, 1, $components);
      $this->parent->attach ($components);
    }
    else {
      $t = ComponentInspector::inspectSet ($this->parent->children);
      throw new ComponentException($this,
        "The component was not found on the parent's children.<h3>The children are:</h3><fieldset>$t</fieldset>");
    }
  }

  /**
   * Replaces the component by its contents in the parent's child list.
   * The component itself is therefore discarded from the components tree.
   */
  public function replaceByContents ()
  {
    $this->replaceBy ($this->children);
  }

  public function setPage (Page $page = null)
  {
    // Do not walk the tree if the page was not changed. Note that detach() does not unset the page references.
    if ($page != $this->page) {
      $this->page = $page;
      foreach ($this->children as $child)
        $child->setPage ($page);
    }
  }

}
