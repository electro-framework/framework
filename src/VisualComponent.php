<?php
namespace impactwave\matisse;

class VisualComponent extends Component implements IAttributes
{
  /**
   * The components base CSS class.
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
   * @return ComponentAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return ButtonAttributes
   */
  public function newAttributes ()
  {
    return new ComponentAttributes($this);
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
      $this->attrs ()->css_class,
      $this->attrs ()->disabled ? 'disabled' : null
    ));
    if (!empty($this->attrs ()->html_attrs))
      echo ' ' . $this->attrs ()->html_attrs;
  }

  protected function postRender ()
  {
    $this->endTag ();
    $this->handleFocus ();
  }

}
