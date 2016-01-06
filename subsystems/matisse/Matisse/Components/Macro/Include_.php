<?php
namespace Selenia\Matisse\Components\Macro;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Properties\Macro\MacroInstanceProperties;

class IncludeProperties extends MacroInstanceProperties
{
  /**
   * The name of the macro to be loaded at parse time and inserted on the current view, replacing the `Include`
   * component.
   *
   * > <p>You **can not** use databinding on this property, as the view model is not available at parse time.
   *
   * @var string
   */
  public $macro = '';
  /**
   * The relative file path of the view to be loaded and rendered at the component's location.
   *
   * <p>Matisse will search for the view on all the view paths registered on the framework.
   *
   * > <p>You **can** use databinding on this property, as the view is loaded at render time and the view model is
   * > available.
   *
   * @var string
   */
  public $view = '';

  function defines ($name, $asSubtag = false)
  {
    // Prevent the occurrence of errors when there are children on the Include tag and the `view` prop. is set.
    return $this->macro ? parent::defines ($name, $asSubtag) : false;
  }

  function getPropertyNames ()
  {
    // Prevent the occurrence of errors when there is text content on the Include tag and the `view` prop. is set.
    return $this->macro ? parent::getPropertyNames () : [];
  }

}

/**
 * Includes either a macro or a view at the component's location.
 *
 * <p>When including a macro, all properties not specific to this component (ex. `macro` and `view`) are converted to
 * macro parameters, including the tag's content, if any.
 */
class Include_ extends MacroInstance
{
  protected static $propertiesClass = IncludeProperties::class;

  /** @var IncludeProperties */
  public $props;

  private $expectingView = false;
  /** @var string */
  private $viewName;

  protected function onCreate (array $props = null, Component $parent)
  {
    $this->parent = $parent;
    $name         = get ($props, 'macro');
    if (exists ($name)) {
      $macro = self::getMacro ($this->context, $parent->page, $name);
      $this->setMacro ($macro);
    }
    else {
      $this->viewName = get ($props, 'view');
      if (!exists ($this->viewName) && !$this->isBound ('view'))
        throw new ComponentException($this,
          "One of these properties must be set:<p><kbd>macro</kbd> | <kbd>view</kbd>");
      $this->allowsChildren = false;
      $this->expectingView  = true;
    }
    parent::onCreate ($props, $parent);
  }

  function onParsingComplete ()
  {
    if (!$this->expectingView)
      parent::onParsingComplete ();
  }

  protected function render ()
  {
    // If we reach this point, `$this->expectingView` is sure to be `true`.

    $name    = $this->viewName ?: $this->evalProp ('view');
    $content = $this->page->getView()->loadFromFile ($name)->getCompiledView ();
    $this->attachAndRender ($content);
  }

}
