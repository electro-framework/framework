<?php
namespace Selenia\Matisse\Components\Macro;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Exceptions\FileIOException;
use Selenia\Matisse\Properties\Macro\MacroInstanceProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

/**
 * A `MacroInstance` is a component that can be represented via any tag that has the same name as the macro it refers
 * to.
 */
class MacroInstance extends Component
{
  const TAG_NAME = 'Call';

  protected static $propertiesClass = MacroInstanceProperties::class;

  public $allowsChildren = true;
  /** @var MacroInstanceProperties */
  public $props;
  /**
   * Points to the component that defines the macro for this instance.
   *
   * @var Macro
   */
  protected $macroInstance;

  function setMacro (Macro $macro)
  {
    $this->macroInstance = $macro;
    if (isset($this->props))
      $this->props->setMacro ($macro);
  }

  /**
   * Loads the macro with the name specified by the `macro` property.
   *
   * @param array|null $props
   * @param Component  $parent
   */
  protected function onCreate (array $props = null, Component $parent)
  {
    $this->parent = $parent;
    $name         = get ($props, 'macro');
    if (exists ($name)) {
      $macro = $this->context->getMacro ($name);
      if (is_null ($macro))
        try {
          // A macro with the given name is not defined yet.
          // Try to load it now.
          $macro = $this->context->loadMacro ($name, $parent);
        }
        catch (FileIOException $e) {
          self::throwUnknownComponent ($this->context, $name, $parent);
        }

      $this->setMacro ($macro);
    }
//    else {
//      $this->viewName = get ($props, 'view');
//      if (!exists ($this->viewName) && !$this->isBound ('view'))
//        throw new ComponentException($this,
//          "One of these properties must be set:<p><kbd>macro</kbd> | <kbd>view</kbd>");
//      $this->allowsChildren = false;
//      $this->expectingView  = true;
//    }
    parent::onCreate ($props, $parent);
  }

  function onParsingComplete ()
  {
    // Move children to default parameter.

    if ($this->hasChildren ()) {
      $def = $this->macroInstance->props->defaultParam;
      if (!empty($def)) {
        $param = $this->macroInstance->getParameter ($def);
        if (!$param)
          throw new ComponentException($this, "Invalid default parameter <kbd>$def</kbd>");
        $type = $this->props->getTypeOf ($def);
        if ($type != type::content && $type != type::metadata)
          throw new ComponentException($this, sprintf (
            "The macro's default parameter <kbd>$def</kbd> can't hold content because its type is <kbd>%s</kbd>.",
            type::getNameOf ($type)));
        $param             = new Metadata($this->context, ucfirst ($def), $type);
        $this->props->$def = $param;
        $param->attachTo ($this);
        $param->setChildren ($this->removeChildren ());
      }
    }

    // Perform macro-expansion.

    $content = $this->macroInstance->apply ($this);
    $this->replaceBy ($content);
  }

}
