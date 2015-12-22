<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Parser\Context;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\Types\type;

class LiteralProperties extends ComponentProperties
{
  protected static $NEVER_DIRTY = ['value' => 1];

  /**
   * @var bool
   */
  public $cdata = false;
  /**
   * @var Metadata|null
   */
  public $content = type::content;
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

class Literal extends Component
{
  protected static $propertiesClass = LiteralProperties::class;

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
   * Returns the component's properties.
   * @return LiteralProperties
   */
  public function props ()
  {
    return $this->props;
  }

  protected function render ()
  {
    if (!is_null ($this->props ()->content))
      $value = $this->props ()->content->value;
    else $value = $this->props ()->value;
    if ($this->props ()->cdata)
      echo '<![CDATA[';
    switch (gettype ($value)) {
      case 'boolean':
        echo $value ? 'true' : 'false';
        break;
      default:
        if ($this->props ()->encode)
          $value = htmlspecialchars ($value);
        if ($this->props ()->nl2br)
          $value = nl2br ($value);
        if (!$this->props ()->whitespace)
          $value = preg_replace ('#^ | $|(>) (<)|(<br ?/?>) #', '$1$2$3', preg_replace ('#\s+#', ' ', $value));
        echo $value;
    }
    if ($this->props ()->cdata)
      echo ']]>';
  }
}
