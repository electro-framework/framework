<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\ContentProperty;
use Selenia\Matisse\Interfaces\PropertiesInterface;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\Types\type;

class RepeatProperties extends ComponentProperties
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
   * @var ContentProperty|null
   */
  public $footer = type::content;
  /**
   * @var mixed
   */
  public $for = type::data;
  /**
   * @var ContentProperty|null
   */
  public $glue = type::content;
  /**
   * @var ContentProperty|null
   */
  public $header = type::content;
  /**
   * @var ContentProperty|null
   */
  public $noData = type::content;
}

class Repeat extends Component implements PropertiesInterface
{
  public $allowsChildren = true;

  /**
   * Returns the component's properties.
   * @return RepeatProperties
   */
  public function props ()
  {
    return $this->props;
  }

  /**
   * Creates an instance of the component's properties.
   * @return RepeatProperties
   */
  public function newProperties ()
  {
    return new RepeatProperties($this);
  }


  protected function render ()
  {
    $attr                  = $this->props ();
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
