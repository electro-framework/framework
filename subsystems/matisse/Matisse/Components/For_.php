<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

class ForProperties extends ComponentProperties
{
  /**
   * @var int
   */
  public $count = 0;
  /**
   * @var string Syntax: 'index:var' or 'var' or not set
   */
  public $each = '';
  /**
   * @var Metadata|null
   */
  public $footer = type::content;
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
  public $else = type::content;
  /**
   * @var mixed
   */
  public $of = type::data;
}

/**
 * Iterates a dataset repeating a block of content for each item.
 */
class For_ extends Component
{
  protected static $propertiesClass = ForProperties::class;

  public $allowsChildren = true;
  /** @var ForProperties */
  public $props;

  protected function viewModel ()
  {
    $this->viewModel = [];
  }

  protected function render ()
  {
    $prop            = $this->props;
    $count           = $prop->get ('count', -1);
    if (exists ($prop->each))
      $this->parseIteratorExp ($prop->each, $idxVar, $itVar);
    else $idxVar = $itVar = null;
    if (!is_null ($for = $prop->of)) {
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
