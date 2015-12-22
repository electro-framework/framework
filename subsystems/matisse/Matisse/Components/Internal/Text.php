<?php
namespace Selenia\Matisse\Components\Internal;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Parser\Context;
use Selenia\Matisse\Properties\Base\ComponentProperties;

class TextProperties extends ComponentProperties
{
  protected static $NEVER_DIRTY = ['value' => 1];

  public $value = '';
}

class Text extends Component
{
  protected static $propertiesClass = TextProperties::class;

  public function __construct (Context $context, $properties = null)
  {
    parent::__construct ($context, $properties);
    $this->page = $this;
    $this->setTagName ('Text');
  }

  public static function from (Context $context, $text)
  {
    return new Text($context, ['value' => $text]);
  }

  /**
   * Returns the component's properties.
   * @return TextProperties
   */
  public function props ()
  {
    return $this->props;
  }

  protected function render ()
  {
    echo $this->props->value;
  }

}
