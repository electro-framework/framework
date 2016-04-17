<?php
namespace Selenia\Matisse\Components\Macro;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Base\CompositeComponent;
use Selenia\Matisse\Components\Internal\DocumentFragment;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Exceptions\FileIOException;
use Selenia\Matisse\Properties\Macro\MacroCallProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

/**
 * A `MacroCall` is a component that can be represented via any tag that has the same name as the macro it refers to.
 */
class MacroCall extends CompositeComponent
{
  const TAG_NAME          = 'Call';
  const allowsChildren    = true;
  const propertiesClass   = MacroCallProperties::class;
  const publishProperties = true;
  /** @var MacroCallProperties */
  public $props;
  /**
   * Points to the component that defines the macro for this instance.
   *
   * @var Macro
   */
  protected $macroInstance;

  function onParsingComplete ()
  {
    // Move children to default parameter.

    if ($this->hasChildren ()) {
      $def = $this->getDefaultParam ();
      if (!empty($def)) {
        if (!$this->props->defines ($def))
          throw new ComponentException($this, "Invalid default property <kbd>$def</kbd>");

        $type = $this->props->getTypeOf ($def);
        if ($type != type::content && $type != type::metadata)
          throw new ComponentException($this, sprintf (
            "The macro's default parameter <kbd>$def</kbd> can't hold content because its type is <kbd>%s</kbd>.",
            type::getNameOf ($type)));

        $param = new Metadata($this->context, ucfirst ($def), $type);
        $this->props->set ($def, $param);
        $param->attachTo ($this);
        $param->setChildren ($this->getChildren ());
      }
      else throw new ComponentException ($this,
        'You may not specify content for this tag because it has no default property');
    }
  }

  function setMacro (Macro $macro)
  {
    $this->macroInstance = $macro;
    if (isset($this->props))
      $this->props->setMacro ($macro);
    $this->setShadowDOM (new DocumentFragment); // this also attaches it, which MUST be done before adding children!
    $this->getShadowDOM ()->addChildren ($macro->getClonedChildren ());
  }

  protected function getDefaultParam ()
  {
    return $this->macroInstance->props->defaultParam;
  }

  /**
   * Loads the macro with the name specified by the `macro` property.
   *
   * @param array|null $props
   * @param Component  $parent
   */
  protected function onCreate (array $props = null, Component $parent = null)
  {
    $this->parent = $parent;
    $name         = get ($props, 'macro');
    if (exists ($name)) {
      $macro = $this->context->getMacro ($name, $this);
      if (is_null ($macro))
        try {
          // A macro with the given name is not defined yet.
          // Try to load it now.
          $macro = $this->context->loadMacro ($name, $parent, $filename);
        }
        catch (FileIOException $e) {
          self::throwUnknownComponent ($this->context, $name, $parent, $filename);
        }

      $this->setMacro ($macro);
    }
    parent::onCreate ($props, $parent);
  }

//  protected function setupViewModel ()
//  {
//    parent::setupViewModel ();
//    foreach ($this->props->getPropertiesOf (type::content, type::metadata, type::collection) as $prop => $v)
//      $this->props->$prop->preRun();
//  }

}
