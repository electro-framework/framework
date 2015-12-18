<?php
namespace Selenia\Matisse\Components\Base;

use Selenia\Matisse\Attributes\Base\VisualComponentAttributes;
use Selenia\Matisse\Interfaces\IAttributes;

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

  public final function addClass ($class)
  {
    $c = " {$this->cssClassName} ";
    $c = str_replace (" $class ", ' ', $c);

    $this->cssClassName = trim ("$c $class");
  }

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

  protected function postRender ()
  {
    $this->end ();
  }

  protected function preRender ()
  {
    if ($this->autoId)
      $this->setAutoId ();
    $this->begin ($this->containerTag);
    $this->attr ('id', $this->attrs ()->id);
    $this->attr ('class', enum (' ',
      $this->className,
      $this->cssClassName,
      $this->attrs ()->class,
      $this->attrs ()->disabled ? 'disabled' : null
    ));
    if (!empty($this->attrs ()->htmlAttrs))
      echo ' ' . $this->attrs ()->htmlAttrs;
  }

}
