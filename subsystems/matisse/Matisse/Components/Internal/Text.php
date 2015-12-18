<?php
namespace Selenia\Matisse\Components\Internal;

use Selenia\Matisse\Attributes\Base\ComponentAttributes;
use Selenia\Matisse\Attributes\DSL\type;
use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Interfaces\IAttributes;
use Selenia\Matisse\Parser\Context;

class TextAttributes extends ComponentAttributes
{
  protected static $NEVER_DIRTY = ['value' => 1];
  public $value = '';

  protected function typeof_value () { return type::text; }
}

class Text extends Component implements IAttributes
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
   * Returns the component's attributes.
   * @return TextAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return TextAttributes
   */
  public function newAttributes ()
  {
    return new TextAttributes($this);
  }

  protected function render ()
  {
    echo $this->attrsObj->value;
  }

}
