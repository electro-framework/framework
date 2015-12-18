<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Attributes\Base\ComponentAttributes;
use Selenia\Matisse\Attributes\DSL\type;
use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Parameter;
use Selenia\Matisse\Interfaces\IAttributes;
use Selenia\Matisse\Parser\Context;

class LiteralAttributes extends ComponentAttributes
{
  protected static $NEVER_DIRTY = ['value' => 1];

  /**
   * @var bool
   */
  public $cdata = false;
  /**
   * @var Parameter|null
   */
  public $content = type::parameter;
  /**
   * @var bool
   */
  public $encode = false;
  /**
   * @var bool
   */
  public $nl2br = false;
  /**
   * @var string
   */
  public $value = '';
  /**
   * @var bool
   */
  public $whitespace = true;
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
