<?php
namespace Selene\Matisse\Components;
use Selene\Matisse\Attributes\ComponentAttributes;
use Selene\Matisse\AttributeType;
use Selene\Matisse\Component;
use Selene\Matisse\IAttributes;

class RepeaterAttributes extends ComponentAttributes
{
  public $glue;
  public $noData;
  public $for;
  public $header;
  public $footer;
  public $count;

  protected function typeof_header () { return AttributeType::SRC; }

  protected function typeof_footer () { return AttributeType::SRC; }

  protected function typeof_glue () { return AttributeType::SRC; }

  protected function typeof_noData () { return AttributeType::SRC; }

  protected function typeof_for () { return AttributeType::DATA; }

  protected function typeof_count () { return AttributeType::NUM; }
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
    $count = $this->attrs ()->get ('count', -1);
    if (!is_null ($this->defaultDataSource = $this->attrs ()->get ('for'))) {
      $first = true;
      foreach ($this->defaultDataSource as $v) {
        if ($first) {
          $first = false;
          $this->renderParameter ('header');
        }
        else $this->renderParameter ('glue');
        $this->renderChildren ();
        if (!--$count) break;
      }
      if (!$first) $this->renderParameter ('footer');
      return;
    }
    if ($count > 0) {
      for ($i = 0; $i < $count; ++$i) {
        if ($i == 0)
          $this->renderParameter ('header');
        else $this->renderParameter ('glue');
        $this->renderChildren ();
      }
      if ($i) {
        $this->renderParameter ('footer');
        return;
      }
    }
    $this->renderParameter ('noData');
  }

}
