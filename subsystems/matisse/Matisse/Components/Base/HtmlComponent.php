<?php
namespace Selenia\Matisse\Components\Base;

use Selenia\Matisse\Properties\Base\HtmlComponentProperties;

class HtmlComponent extends Component
{
  protected static $propertiesClass = HtmlComponentProperties::class;
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
   * Returns the component's properties.
   * @return HtmlComponentProperties
   */
  public function props ()
  {
    return $this->props;
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
    $this->attr ('id', $this->props ()->id);
    $this->attr ('class', enum (' ',
      $this->className,
      $this->cssClassName,
      $this->props ()->class,
      $this->props ()->disabled ? 'disabled' : null
    ));
    if (!empty($this->props ()->htmlAttrs))
      echo ' ' . $this->props ()->htmlAttrs;
  }

}
