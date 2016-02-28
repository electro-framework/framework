<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\CompositeComponent;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Exceptions\FileIOException;
use Selenia\Matisse\Properties\Base\ComponentProperties;

class IncludeProperties extends ComponentProperties
{
  /**
   * The fully qualified PHP class name of the component class to load as a child of the Include.
   *
   * <p>If {@see $view} or {@see $template} are defined, they will be set as the component's view.
   *
   * @var string
   */
  public $class = '';
  /**
   * The relative path and file name of the file to be loaded and rendered at the component's location.
   *
   * <p>Matisse will compute the final path from the root directory of the application.
   *
   * > <p>You **can** use databinding on this property, as the view is loaded at render time and the view model is
   * > available.
   *
   * @var string
   */
  public $file = '';
  /**
   * When true, the component outputs all script imports and embedded scripts for the current document.
   *
   * @var bool
   */
  public $scripts = false;
  /**
   * When true, the component outputs all CSS stylesheet imports and embedded styles for the current document.
   *
   * @var bool
   */
  public $styles = false;
  /**
   * Defines an inline template for the view.
   *
   * <p>This is usually used with a databinding expression with the `{!! !!}` syntax to inject a dynamic template from a
   * viewModel property or from a content block.
   *
   * @var string
   */
  public $template = '';
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
}

/**
 * The **Include** component is capable of rendering content from multiple source types.
 *
 * <p>With it, you can:
 *
 * 1. load raw markup files;
 * - load controller-less views;
 * - load view-less components;
 * - load composite components where each component determines its view;
 * - load composite components defining or overriding their view independently;
 * - inject managed scripts into the page;
 * - inject managed stylesheets into the page.
 *
 * <p>One common use of Include is to assign controllers to view partials/layouts, therefore encapsulating their
 * functionality and freeing your page controller code from having to handle each and all of them that are included on
 * the page.
 */
class Include_ extends CompositeComponent
{
  protected static $propertiesClass = IncludeProperties::class;

  /** @var IncludeProperties */
  public $props;

  protected function render ()
  {
    $prop       = $this->props;
    $ctx        = $this->context;
    $controller = $prop->class;

    // Resolve controller for the view (if applicable)

    if (!exists ($controller) && exists ($prop->view))
      $controller = $ctx->findControllerForView ($prop->view);

    if (exists ($prop->template)) {
      $exp = get ($this->bindings, 'template');
      if (str_beginsWith ($exp, '{{'))
        throw new ComponentException($this,
          "When binding a value to the <kbd>template</kbd> property, you must use the databinding-without-escaping syntax <kbd>{!! !!}</kbd>");
      if (exists ($controller)) {
        $skin = $ctx->createComponent ($controller, $this);
        if (!$skin instanceof CompositeComponent)
          throw new ComponentException($this,
            "Component <kbd>$controller</kbd> is not a <kbd>CompositeComponent</kbd> instance, so it can't be a controler");
        $skin->template = $prop->template;
        $this->attachAndRender ($skin);
      }
      else {
        $this->template = $prop->template;
        parent::render ();
      }
    }

    elseif (exists ($prop->view)) {
      if (exists ($controller)) {
        $skin = $ctx->createComponent ($controller, $this);
        if (!$skin instanceof CompositeComponent)
          throw new ComponentException($this,
            "Component <kbd>$controller</kbd> is not a <kbd>CompositeComponent</kbd> instance, so it can't be the controler of <kbd>$prop->view</kbd>");
        $skin->templateUrl = $prop->view;
        $this->attachAndRender ($skin);
      }
      else {
        $this->templateUrl = $prop->view;
        parent::render ();
      }
    }

    else if (exists ($prop->file)) {
      $fileContent = loadFile ($prop->file);
      if ($fileContent === false)
        throw new FileIOException($prop->file, 'read', explode (PATH_SEPARATOR, get_include_path ()));
      echo $fileContent;
    }

    else if ($prop->styles) {
      $ctx->outputStyles ();
    }

    else if ($prop->scripts) {
      $ctx->outputScripts ();
    }
  }

}
