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
   * is being repeatedly re-rendered (being a child of a `<Repeat>` component, for instance), the
   * attribute will become unstable. Use this property instead, which is reset for every rendering of the component.
   *
   * @var string
   */
  public  $cssClassName = '';
  /** @var HtmlComponentProperties */
  public $props;
  /**
   * Override to select a different tag as the component container.
   *
   * @var string
   */
  protected $containerTag = 'div';
  private $originalCssClassName;

  function addClass ($class)
  {
    $c = " {$this->cssClassName} ";
    $c = str_replace (" $class ", ' ', $c);

    $this->cssClassName = trim ("$c $class");
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
    $this->attr ('id', $this->props->id);
    $this->attr ('class', enum (' ',
      $this->className,
      $this->cssClassName,
      $this->props->class,
      $this->props->disabled ? 'disabled' : null
    ));
    if (exists ($this->props->htmlAttrs))
      echo ' ' . $this->props->htmlAttrs;
  }

  function run ()
  {
    if ($this->renderCount)
      $this->cssClassName = $this->originalCssClassName;
    else $this->originalCssClassName = $this->cssClassName;
    parent::run ();
  }

}
