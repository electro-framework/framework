<?php
namespace Selene\Matisse\Base;
use Selene\Matisse\AttributeType;
use Selene\Matisse\Component;
use Selene\Matisse\Attributes\ComponentAttributes;
use Selene\Matisse\Context;
use Selene\Matisse\IAttributes;

class TextAttributes extends ComponentAttributes
{
  public $value = '';

  protected function typeof_value () { return AttributeType::TEXT; }

  protected static $NEVER_DIRTY = ['value' => 1];
}

class Text extends Component implements IAttributes
{
  public function __construct (Context $context, $properties = null)
  {
    parent::__construct ($context, $properties);
    $this->page = $this;
    $this->setTagName ('text');
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
