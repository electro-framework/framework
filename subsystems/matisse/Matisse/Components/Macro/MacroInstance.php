<?php
namespace Selenia\Matisse\Components\Macro;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Parser\Context;
use Selenia\Matisse\Properties\Macro\MacroInstanceProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

/**
 * A `MacroInstance` is a component that can be represented via any tag that has the same name as the macro it refers
 * to.
 */
class MacroInstance extends Component
{
  protected static $propertiesClass = MacroInstanceProperties::class;

  public $allowsChildren = true;
  /** @var MacroInstanceProperties */
  public $props;
  /**
   * Points to the component that defines the macro for this instance.
   *
   * @var Macro
   */
  protected $macro;

  public function __construct (Context $context, $tagName, Macro $macro, array $attributes = null)
  {
    $this->macro = $macro; //must be defined before the parent constructor is called
    parent::__construct ($context);
    $this->props->setMacro ($macro);
    if ($attributes)
      $this->props->apply ($attributes);
    $this->setTagName ($tagName);
  }

  public function onParsingComplete ()
  {
    // Move children to default parameter.

    if ($this->hasChildren ()) {
      $def = $this->macro->props->defaultParam;
      if (!empty($def)) {
        $param = $this->macro->getParameter ($def);
        if (!$param)
          throw new ComponentException($this, "The macro's declared default parameter is invalid: $def");
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

    $content = $this->macro->apply ($this);
    $this->replaceBy ($content);
  }

}
