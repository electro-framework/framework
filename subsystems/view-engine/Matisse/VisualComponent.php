<?php
namespace Selenia\Matisse;

use Selenia\Matisse\Attributes\VisualComponentAttributes;

class VisualComponent extends Component implements IAttributes
{
  /**
   * The component's runtime CSS classes.
   *
   * You should never change the `class` attribute at rendering time, because if the component
   * is being repeatedly re-rendered (being part of a repeater section, for instance), the
   * attribute will become instable. Use this property instead.
   * @var string
   */
  public $cssClassName = '';

  /**
   * Override to select a different tag as the component container.
   * @var string
   */
  protected $containerTag = 'div';

  /**
   * Returns the component's attributes.
   * @return VisualComponentAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return VisualComponentAttributes
   */
  public function newAttributes ()
  {
    return new VisualComponentAttributes($this);
  }

  public final function addClass ($class)
  {
    $c = " {$this->cssClassName} ";
    $c = str_replace (" $class ", ' ', $c);

    $this->cssClassName = trim ("$c $class");
  }

  protected function preRender ()
  {
    if ($this->autoId)
      $this->setAutoId ();
    $this->beginTag ($this->containerTag);
    $this->addAttribute ('id', $this->attrs ()->id);
    $this->addAttribute ('class', enum (' ',
      $this->className,
      $this->cssClassName,
      $this->attrs ()->class,
      $this->attrs ()->disabled ? 'disabled' : null
    ));
    if (!empty($this->attrs ()->htmlAttrs))
      echo ' ' . $this->attrs ()->htmlAttrs;
  }

  protected function postRender ()
  {
    $this->endTag ();
  }

}
