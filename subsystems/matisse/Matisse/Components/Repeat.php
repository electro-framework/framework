<?php
namespace Selenia\Matisse\Components;
use Selenia\Matisse\Attributes\ComponentAttributes;
use Selenia\Matisse\AttributeType;
use Selenia\Matisse\Component;
use Selenia\Matisse\IAttributes;

class RepeaterAttributes extends ComponentAttributes
{
  public $as;
  public $count;
  public $footer;
  public $for;
  public $glue;
  public $header;
  public $noData;

  protected function typeof_as () { return AttributeType::TEXT; }

  protected function typeof_count () { return AttributeType::NUM; }

  protected function typeof_footer () { return AttributeType::SRC; }

  protected function typeof_for () { return AttributeType::DATA; }

  protected function typeof_glue () { return AttributeType::SRC; }

  protected function typeof_header () { return AttributeType::SRC; }

  protected function typeof_noData () { return AttributeType::SRC; }
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
