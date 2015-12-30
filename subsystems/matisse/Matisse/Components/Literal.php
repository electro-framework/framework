<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Parser\Context;
use Selenia\Matisse\Properties\Base\PropertiesWithChangeTracking;

class LiteralProperties extends PropertiesWithChangeTracking
{
  protected static $NEVER_DIRTY = ['value' => 1];

  /**
   * @var bool Output a CDATA section. This is useful for XML documents only.
   */
  public $cdata = false;
  /**
   * @var bool Collapse white space?
   */
  public $collapse = false;
  /**
   * Escape (HTML-encode) the resulting text?
   * > <p>**Note:** values assigned via databinding are already escaped by default (unless you use the raw output
   * > expression syntax), so there's usually no need to enable this option.
   * > <p>This is useful when the content is provided, not by the `value` property, but by rendering the element's own
   * > content (child components).
   *
   * @var bool
   */
  public $escape = false;
  /**
   * @var bool Convert line breaks to BR tags?
   */
  public $nl2br = false;
  /**
   * The text to be output. If the element has content, it will be used instead and this property is ignored.
   *
   * @var string
   */
  public $value = '';
}

/**
 * The Literal component is a more advanced version of the internal Text component. It outputs its content as text
 * (plain text or HTML) and it provides configurable options to encode and/or transform that text.
 *
 * <p>Instances of this type can be specified via a markup tag (unlike the Text component), but they can also be
 * generated automatically by the parser when it encounters a binding expression outside of a tag attribute.
 */
class Literal extends Component
{
  protected static $propertiesClass = LiteralProperties::class;

  /** @var LiteralProperties */
  public $props;

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

  protected function render ()
  {
    $value = $this->hasChildren () ? $this->getContent () : $this->props->value;
    if ($this->props->cdata)
      echo '<![CDATA[';
    switch (gettype ($value)) {
      case 'boolean':
        echo $value ? 'true' : 'false';
        break;
      default:
        if ($this->props->escape)
          $value = htmlentities ($value, ENT_QUOTES, 'UTF-8', false);
        if ($this->props->nl2br)
          $value = nl2br ($value);
        if ($this->props->collapse)
          $value = preg_replace ('#^ | $|(>) (<)|(<br ?/?>) #', '$1$2$3', preg_replace ('#\s+#', ' ', $value));
        echo $value;
    }
    if ($this->props->cdata)
      echo ']]>';
  }
}
