<?php
namespace Selenia\Matisse\Components\Macro;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Interfaces\MacroExtensionInterface;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\TypeSystem\is;
use Selenia\Matisse\Properties\TypeSystem\type;

class MacroParamProperties extends ComponentProperties
{
  /**
   * @var mixed
   */
  public $default = type::any;
  /**
   * @var string
   */
  public $name = [type::string, is::required];

}

/**
 */
class MacroParam extends Component implements MacroExtensionInterface
{
  const propertiesClass = MacroParamProperties::class;

  /** @var MacroParamProperties */
  public $props;

  function onMacroApply (Macro $macro, MacroCall $call, array &$components, &$index)
  {
    $prop = $this->props;

    if (isset($prop->default)) {
      $name = $prop->name;
      if (!isset ($call->props->$name))
        $call->props->$name = $prop->default;
    }
//    $this->remove ();
    array_splice ($components, $index, 1);
    --$index;
    return false;
  }
}
