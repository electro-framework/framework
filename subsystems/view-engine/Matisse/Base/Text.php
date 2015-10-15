<?php
namespace Selenia\Matisse\Base;
use Selenia\Matisse\AttributeType;
use Selenia\Matisse\Component;
use Selenia\Matisse\Attributes\ComponentAttributes;
use Selenia\Matisse\Context;
use Selenia\Matisse\IAttributes;

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
