<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\CompositeComponent;
use Selenia\Matisse\Exceptions\FileIOException;
use Selenia\Matisse\Properties\Base\ComponentProperties;

class IncludeProperties extends ComponentProperties
{
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
 * Renders a dynamic view or an arbitrary static file.
 *
 * <p>When rendering a view, the view's rendering context (and associated view model) come from the current rendering
 * context.
 */
class Include_ extends CompositeComponent
{
  protected static $propertiesClass = IncludeProperties::class;

  /** @var IncludeProperties */
  public $props;

  protected function render ()
  {
    $prop = $this->props;
    if (exists ($prop->view)) {
      $this->templateUrl = $prop->view;
      parent::render ();
    }
    else if (exists ($prop->file)) {
      $fileContent = loadFile ($prop->file);
      if ($fileContent === false)
        throw new FileIOException($prop->file, 'read', explode (PATH_SEPARATOR, get_include_path ()));
      echo $fileContent;
    }
    else if ($prop->styles)
      $this->context->outputStyles ();
    else if ($prop->scripts)
      $this->context->outputScripts ();
  }

}
