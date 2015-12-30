<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

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
   * @var Metadata|null
   */
  public $footer = type::content;
  /**
   * @var mixed
   */
  public $for = type::data;
  /**
   * @var Metadata|null
   */
  public $glue = type::content;
  /**
   * @var Metadata|null
   */
  public $header = type::content;
  /**
   * @var Metadata|null
   */
  public $noData = type::content;
}

class Repeat extends Component
{
  protected static $propertiesClass = RepeatProperties::class;

  public $allowsChildren = true;
  /** @var RepeatProperties */
  public $props;

  protected function render ()
  {
    $attr                  = $this->props;
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
