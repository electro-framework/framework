<?php
namespace Selenia\Matisse\Components\Internal;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Interfaces\PropertiesInterface;
use Selenia\Matisse\Parser\Context;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\Types\type;

class TextProperties extends ComponentProperties
{
  protected static $NEVER_DIRTY = ['value' => 1];
  public $value = '';

  protected function typeof_value () { return type::string; }
}

class Text extends Component implements PropertiesInterface
{
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

  /**
   * Creates an instance of the component's properties.
   * @return TextProperties
   */
  public function newProperties ()
  {
    return new TextProperties($this);
  }

  protected function render ()
  {
    echo $this->props->value;
  }

}
