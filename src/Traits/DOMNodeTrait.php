<?php
namespace Selene\Matisse\Traits;

use Selene\Matisse\Component;
use Selene\Matisse\ComponentInspector;
use Selene\Matisse\Components\Parameter;
use Selene\Matisse\Exceptions\ComponentException;

/**
 * Provides an API for manipulating DOM nodes on a tree of components.
 *
 * It's applicable to the Component class.
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
   * @var Component[]
   */
  public $children = null;
  /**
   * Points to the parent component in the page hierarchy.
   * It is set to NULL if the component is the top one (a Page instance) or if it's standalone.
   *
   * @var Component
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

  public function __clone ()
  {
    if (isset($this->attrsObj)) {
      $this->attrsObj = clone $this->attrsObj;
      $this->attrsObj->setComponent ($this);
    }
    if (isset($this->children))
      $this->children = self::cloneComponents ($this->children, $this);
  }

  public final function addChild (Component $child)
  {
    if (isset($child)) {
      $this->children[] = $child;
      $this->attach ($child);
    }
  }

  public final function addChildren (array $children = null)
  {
    if (isset($children))
      foreach ($children as $child)
        $this->addChild ($child);
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
    $this->page   = $parent->page;
  }

  public function detach ()
  {
    $this->parent = $this->page = null;
  }

  public final function getChildren ($attrName)
  {
    if (isset($this->attrsObj->$attrName)) {
      $p = $this->attrsObj->$attrName;
      if ($p instanceof Parameter)
        return $p->children;
      throw new ComponentException($this,
        "Can' get children of attribute <b>$attrName</b>, which has a value of type <b>" . gettype ($p) . '</b>.');
    }

    return null;
  }

  public final function setChildren (array $children = null, $attach = true)
  {
    if (isset($children)) {
      $this->children = $children;
      if ($attach)
        $this->attach ($children);
    }
  }

  public final function getClonedChildren ($attrName)
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
    if (!isset($this->parent->children))
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
   * Replaces the component by the specified componentes in the parent's child list.
   * The component itself is discarded from the components tree.
   *
   * @param array $components
   * @throws ComponentException
   */
  public final function replaceBy (array $components = null)
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
  public final function replaceByContents ()
  {
    $this->replaceBy ($this->children);
  }

}
