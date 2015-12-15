<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Attributes\GenericComponentAttributes;
use Selenia\Matisse\Component;
use Selenia\Matisse\IAttributes;

/**
 * The Body component allows one to set the body tag's attributes and/or, optionally, specify the content that will
 * be wrapped by the `form` HTML element.
 */
class Body extends Component implements IAttributes
{
  public $allowsChildren   = true;
  public $defaultAttribute = 'content';

  /**
   * Returns the component's attributes.
   * @return GenericComponentAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return GenericComponentAttributes
   */
  public function newAttributes ()
  {
    return new GenericComponentAttributes($this);
  }

  /**
   * Adds the content of the `content` parameter to the page's body element.
   * Adds the attributes defined via the html-attrs parameter to the page's body element.
   */
  protected function render ()
  {
    ob_start ();
    self::renderSet ($this->getChildren ('content'));
    $html = ob_get_clean ();

    if (!empty($html))
      $this->page->bodyContent .= $html;

    $attrs = $this->attrs ()->getAll ();
    $a     = [];
    foreach ($attrs as $k => $v) {
      if (isset($v) && $v !== '' && is_scalar ($v))
        $a[$k] = $v;
    }
    $this->page->bodyAttrs = array_merge ($this->page->bodyAttrs, $a);
  }
}

