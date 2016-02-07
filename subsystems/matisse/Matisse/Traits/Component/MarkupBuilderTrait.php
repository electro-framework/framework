<?php
namespace Selenia\Matisse\Traits\Component;

use Selenia\Matisse\Components\Literal;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Parser\Context;
use Selenia\Matisse\Parser\Tag;

/**
 * Provides an API for generating structured HTML code.
 *
 * It's applicable to the Component class.
 *
 * @property Context context
 */
trait MarkupBuilderTrait
{
  /**
   * An array containing the names of the HTML tags which must not have a closing tag.
   *
   * @var array
   */
  private static $VOID_ELEMENTS = [
    'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source',
    'track', 'wbr',
  ];
  private $tag;
  private $tags = [];

  protected function addAttrs ($attrs)
  {
    foreach ($attrs as $name => $val)
      $this->attr ($name, $val);
  }

  protected function attr ($name, $value = '')
  {
    echo isset($value) && $value !== false ? (strlen ($value) && $value !== true ? " $name=\"$value\"" : " $name") : '';
  }

  protected function attr2 ($name, $value1, $value2)
  {
    if (strlen ($value2))
      echo " $name=\"$value1$value2\"";
  }

  protected function attrIf ($checkValue, $name, $value = '')
  {
    if ($checkValue)
      $this->attr ($name, $value);
  }

  protected function attrValue ($value)
  {
    if (strlen ($value)) {
      echo $this->tag->attrName;
      $this->tag->attrName = '';
      if ($this->tag->isFirstValue) {
        echo $value;
        $this->tag->isFirstValue = false;
      }
      else
        echo $this->tag->attrSep . $value;
    }
  }

  protected function attrValue2 ($value1, $value2)
  {
    if (strlen ($value2)) {
      echo $this->tag->attrName;
      $this->tag->attrName = '';
      if ($this->tag->isFirstValue) {
        echo $value1 . $value2;
        $this->tag->isFirstValue = false;
      }
      else
        echo $this->tag->attrSep . $value1 . $value2;
    }
  }

  protected function attrValueIf ($checkValue, $value)
  {
    if ($checkValue) {
      echo $this->tag->attrName;
      $this->tag->attrName = '';
      if ($this->tag->isFirstValue) {
        echo $value;
        $this->tag->isFirstValue = false;
      }
      else
        echo $this->tag->attrSep . $value;
    }
  }

  protected function beginAttr ($name, $value = null, $attrSep = ' ')
  {
    if (strlen ($value) == 0) {
      $this->tag->attrName     = " $name=\"";
      $this->tag->isFirstValue = true;
    }
    else
      echo " $name=\"$value";
    $this->tag->attrSep = $attrSep;
  }

  protected function beginCapture ()
  {
    ob_start (null, 0);
  }

  /**
   * Always call this before outputting any content inside the wrapper tag.
   */
  protected function beginContent ()
  {
    if (isset($this->tag) && !$this->tag->isContentSet) {
      echo '>';
      $this->tag->isContentSet = true;
    }
  }

  protected function begin ($name, array $attributes = null)
  {
    if (isset($this->tag)) {
      $this->beginContent ();
      array_push ($this->tags, $this->tag);
    }
    $this->tag       = new Tag();
    $this->tag->name = strtolower ($name);
    echo '<' . $name;
    if ($attributes)
      foreach ($attributes as $k => $v)
        $this->attr ($k, $v);
  }

  protected function endAttr ()
  {
    if ($this->tag->attrName != '')
      $this->tag->attrName = '';
    else
      echo '"';
    $this->tag->isFirstValue = false;
  }

  protected function endCapture ()
  {
    $literal = $this->getLiteral ();
    if (isset($literal))
      $this->addChild ($literal);
  }

  protected function end ()
  {
    if (is_null ($this->tag))
      throw new ComponentException($this, "Unbalanced beginTag() / endTag() pairs.");
    $name = $this->tag->name;
    $x    = $this->context->debugMode ? "\n" : '';
    if ($this->tag->isContentSet)
      echo "</$name>$x";
    elseif (array_search ($name, self::$VOID_ELEMENTS) !== false)
      echo "/>$x";
    else
      echo "></$name>$x";
    $this->tag = array_pop ($this->tags);
  }

  protected function flushCapture ()
  {
    $this->endCapture ();
    ob_start (null, 0);
  }

  /**
   * @return Literal
   */
  protected function getLiteral ()
  {
    $this->beginContent ();
    $text = ob_get_clean ();
    if (strlen ($text))
      return Literal::from ($this->context, $text);

    return null;
  }

  protected function setContent ($content)
  {
    if (!$this->tag->isContentSet)
      echo '>';
    echo $content;
    $this->tag->isContentSet = true;
  }

  protected function tag ($name, array $parameters = null, $content = null)
  {
    $this->begin ($name, $parameters);
    if (!is_null ($content))
      $this->setContent ($content);
    $this->end ();
  }

}
