<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Components\Macro\Macro;
use Selenia\Matisse\Components\Macro\MacroCall;
use Selenia\Matisse\Interfaces\MacroExtensionInterface;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

class IfProperties extends ComponentProperties
{
  /**
   * @var bool When true, the evaluateuation of the condition is performed at parsing-time.
   */
  public $atParsingTime = false;
  /**
   * @var Metadata[]
   */
  public $case = type::collection;
  /**
   * @var Metadata|null
   */
  public $else = type::content;
  /**
   * @var string
   */
  public $is = type::string;
  /**
   * @var bool
   */
  public $isSet = false;
  /**
   * @var bool
   */
  public $isTrue = false;
  /**
   * @var string
   */
  public $matches = '';
  /**
   * > **Mote:** it doesn't work with databinding.
   *
   * @var bool
   */
  public $not = false;
  /**
   * @var mixed
   */
  public $the = type::any;
}

/**
 * Rendes content blocks conditionally.
 *
 * ##### Syntax:
 * ```
 * <If the="value1" is="value2">
 *   content if true
 *   <Else> content if false </Else>
 * </If>
 *
 * <If is="value"> content if value is truthy </If>
 *
 * <If not is="value"> content if value is falsy </If>
 *
 * <If the="value" isTrue> content if value is truthy </If>
 *
 * <If the="value" not isTrue> content if value is falsy </If>
 *
 * <If the="value" isSet> content if value is different from both null and the empty string </If>
 *
 * <If the="value" not isSet> content if value is equal to null or to an empty string </If>
 *
 * <If the="value" matches="regexp"> content if value matches the regular expression </If>
 *
 * <If the="value" not matches="regexp"> content if value doesn't matche the regular expression </If>
 *
 * <If the="value">
 *   <Case is="value1"> content if value == value1 </Case>
 *   ...
 *   <Case is="valueN"> content if value == valueN </Case>
 *   <Else> content if no match </Else>
 * </If>
 * ```
 */
class If_ extends Component implements MacroExtensionInterface
{
  protected static $propertiesClass = IfProperties::class;

  public $allowsChildren = true;
  /** @var IfProperties */
  public $props;

  function onMacroApply (Macro $macro, MacroCall $call, array &$components, &$index)
  {
    $prop = $this->props;

    if (!$prop->atParsingTime)
      return true;

    $result = $this->evaluate ();
    if ($result) {
//      $this->replaceBy ($result);
      array_splice ($components, $index, 1, $result);
    }
    else {
//      $this->remove ();
      array_splice ($components, $index, 1);
    }
    --$index;
    return false;
  }

  protected function evaluate ()
  {
    $prop = $this->props;

    $v   = $prop->get ('the');
    $is  = $prop->get ('is');
    $not = $prop->not;

    if (isset($is)) {
      if (!isset($v)) {
        $is = toBool ($is);
        $v  = true;
      }
      if ($v == $is xor $not)
        return $this->getChildren ();
      return $this->getChildren ('else');
    }

    if ($prop->isSet) {
      if (exists ($v) xor $not)
        return $this->getChildren ();
      return $this->getChildren ('else');
    }

    if ($prop->isTrue) {
      if (toBool ($v) xor $not)
        return $this->getChildren ();
      return $this->getChildren ('else');
    }

    if (exists ($prop->matches)) {
      if (preg_match ("%$prop->matches%", $v) xor $not)
        return $this->getChildren ();
      return $this->getChildren ('else');
    }

    if ($prop->case) {
      foreach ($prop->case as $param) {
        if ($v == $param->props->is)
          return $param->getChildren ();
      }
      return $this->getChildren ('else');
    }

    if (toBool ($v) xor $not)
      return $this->getChildren ();

    return $this->getChildren ('else');
  }

  protected function render ()
  {
    $result = $this->evaluate ();
    if ($result)
      $this->renderSet ($result);
  }
}
