<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Attributes\Base\ComponentAttributes;
use Selenia\Matisse\Attributes\DSL\type;
use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Parameter;
use Selenia\Matisse\Interfaces\IAttributes;

class RepeaterAttributes extends ComponentAttributes
{
  /**
   * @var string
   */
  public $as = '';
  /**
   * @var int
   */
  public $count = 0;
  /**
   * @var Parameter|null
   */
  public $footer = type::parameter;
  /**
   * @var mixed
   */
  public $for = type::data;
  /**
   * @var Parameter|null
   */
  public $glue = type::parameter;
  /**
   * @var Parameter|null
   */
  public $header = type::parameter;
  /**
   * @var Parameter|null
   */
  public $noData = type::parameter;
}

class Repeat extends Component implements IAttributes
{
  public $allowsChildren = true;

  /**
   * Returns the component's attributes.
   * @return RepeaterAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return RepeaterAttributes
   */
  public function newAttributes ()
  {
    return new RepeaterAttributes($this);
  }


  protected function render ()
  {
    $attr                  = $this->attrs ();
    $count                 = $attr->get ('count', -1);
    $this->contextualModel = [];
    $this->parseIteratorExp ($attr->as, $idxVar, $itVar);
    if (!is_null ($for = $attr->get ('for'))) {
      $first = true;
      foreach ($for as $i => $v) {
        if ($idxVar)
          $this->contextualModel[$idxVar] = $i;
        $this->contextualModel[$itVar] = $v;
        if ($first) {
          $first = false;
          $this->renderChildren ('header');
        }
        else $this->renderChildren ('glue');
        $this->renderChildren ();
        if (!--$count) break;
      }
      if (!$first) $this->renderChildren ('footer');
      return;
    }
    if ($count > 0) {
      for ($i = 0; $i < $count; ++$i) {
        $this->contextualModel[$idxVar] = $this->contextualModel[$itVar] = $i;
        if ($i == 0)
          $this->renderChildren ('header');
        else $this->renderChildren ('glue');
        $this->renderChildren ();
      }
      if ($i) {
        $this->renderChildren ('footer');
        return;
      }
    }
    $this->renderChildren ('noData');
  }

}
