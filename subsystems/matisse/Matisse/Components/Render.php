<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Text;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Exceptions\FileIOException;
use Selenia\Matisse\Properties\Base\ComponentProperties;

class RenderProperties extends ComponentProperties
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
class Render extends Component
{
  protected static $propertiesClass = RenderProperties::class;

  /** @var RenderProperties */
  public $props;

  protected function render ()
  {
    $prop = $this->props;
    if (exists ($prop->view)) {
      $content = $this->page->getView ()->loadFromFile ($prop->view)->getCompiledView ();
    }
    else if (exists ($prop->file)) {
      $fileContent = loadFile ($prop->file);
      if (!$fileContent)
        throw new FileIOException($prop->file, 'read', explode (PATH_SEPARATOR, get_include_path ()));
      $content = Text::from ($this->context, $fileContent);
    }
    else throw new ComponentException($this,
      "One of these properties must be set:<p><kbd>file</kbd> | <kbd>view</kbd>");
    $this->attachAndRender ($content);
  }

}
