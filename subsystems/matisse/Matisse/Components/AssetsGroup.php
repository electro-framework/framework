<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Properties\Base\ComponentProperties;

class AssetsGroupProperties extends ComponentProperties
{
  /**
   * @var bool
   */
  public $prepend = false;
}

class AssetsGroup extends Component
{
  protected static $propertiesClass = AssetsGroupProperties::class;

  public $allowsChildren = true;
  /** @var AssetsGroupProperties */
  public $props;

  /**
   * Groups Script components under the same assets context.
   */
  protected function render ()
  {
    $this->context->beginAssetsContext ($this->props->prepend);
    $this->renderContent ();
    $this->context->endAssetsContext ();
  }

}

