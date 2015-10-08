<?php
namespace Selenia\Matisse\Components;
use Selenia\Matisse\AttributeType;
use Selenia\Matisse\Component;
use Selenia\Matisse\Attributes\ComponentAttributes;
use Selenia\Matisse\Context;
use Selenia\Matisse\IAttributes;

class LiteralAttributes extends ComponentAttributes
{
  public $value      = '';
  public $encode     = false;
  public $whitespace = true;
  public $cdata      = false;
  /** @var Parameter */
  public $content = null;
  public $nl2br   = false;

  protected function typeof_value () { return AttributeType::TEXT; }

  protected function typeof_encode () { return AttributeType::BOOL; }

  protected function typeof_whitespace () { return AttributeType::BOOL; }

  protected function typeof_cdata () { return AttributeType::BOOL; }

  protected function typeof_content () { return AttributeType::SRC; }

  protected function typeof_nl2br () { return AttributeType::BOOL; }

  protected static $NEVER_DIRTY = ['value' => 1];
}

class Literal extends Component implements IAttributes
{
  public function __construct (Context $context, $properties = null)
  {
    parent::__construct ($context, $properties);
    $this->page = $this;
    $this->setTagName ('Literal');
  }

  public static function from (Context $context, $text)
  {
    return new Literal($context, ['value' => $text]);
  }

  /**
   * Returns the component's attributes.
   * @return LiteralAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return LiteralAttributes
   */
  public function newAttributes ()
  {
    return new LiteralAttributes($this);
  }

  protected function render ()
  {
    if (!is_null ($this->attrs ()->content))
      $value = $this->attrs ()->content->value;
    else $value = $this->attrs ()->value;
    if ($this->attrs ()->cdata)
      echo '<![CDATA[';
    switch (gettype ($value)) {
      case 'boolean':
        echo $value ? 'true' : 'false';
        break;
      default:
        if ($this->attrs ()->encode)
          $value = htmlspecialchars ($value);
        if ($this->attrs ()->nl2br)
          $value = nl2br ($value);
        if (!$this->attrs ()->whitespace)
          $value = preg_replace ('#^ | $|(>) (<)|(<br ?/?>) #', '$1$2$3', preg_replace ('#\s+#', ' ', $value));
        echo $value;
    }
    if ($this->attrs ()->cdata)
      echo ']]>';
  }
}
