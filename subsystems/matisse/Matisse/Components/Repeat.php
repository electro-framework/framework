<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

class RepeatProperties extends ComponentProperties
{
  /**
   * @var string Syntax: 'index:var' or 'var' or not set
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
    $prop            = $this->props;
    $count           = $prop->get ('count', -1);
    $this->viewModel = [];
    if (exists ($prop->as))
      $this->parseIteratorExp ($prop->as, $idxVar, $itVar);
    else $idxVar = $itVar = null;
    if (!is_null ($for = $prop->for)) {
      $first = true;
      foreach ($for as $i => $v) {
        if ($idxVar)
          $this->viewModel[$idxVar] = $i;
        $this->viewModel[$itVar] = $v;
        if ($first) {
          $first = false;
          $this->renderChildren ('header');
        }
        else $this->renderChildren ('glue');
        $this->renderChildren ();
        if (!--$count) break;
      }
      if ($first)
        $this->renderChildren ('noData');
      else $this->renderChildren ('footer');
      return;
    }
    if ($count > 0) {
      for ($i = 0; $i < $count; ++$i) {
        $this->viewModel[$idxVar] = $this->viewModel[$itVar] = $i;
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
