<?php
namespace Selenia\Matisse\Components\Base;

use Selenia\Interfaces\RenderableInterface;
use Selenia\Interfaces\Views\ViewInterface;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Lib\IsolateViewModel;
use Selenia\ViewEngine\Engines\MatisseEngine;

/**
 * A component that delegates its rendering to a separate template (either internal or external to the component),
 * which is parsed, compiled and (in some cases) rendered by a view engine.
 *
 * <p>Composite components are composed of both a "source DOM" and a view (or "shadow DOM").
 *
 * <p>The source DOM is the set of original DOM subtrees (from children or from properties) provided to the component
 * on the document by its author. It can be used to provide metadata and/or document fragments for inclusion on the
 * view. This is the DOM that simple (non-composite) components work with.
 *
 * <p>Composite components do not render themselves directly, instead they delegate rendering to a view, which parses,
 * compiles and renders a template with the help of a view engine.
 *
 * <p>The view engine can be Matisse, in which case the view is compiled to a "shadow DOM" of components that can
 * render themselves, or it can be another templating engine, which usually is also responsible for rendering the
 * template.
 *
 * > <p>**Note:** Matisse components on the view can, in turn, be composite components that have their own templates,
 * and so on recursively. **But** the rendered output of a composite component must be final rendered markup, it can
 * not be again a template that requires further processing.
 */
class CompositeComponent extends Component
{
  /**
   * An inline/embedded template to be rendered as the component's appearance.
   *
   * <p>The view engine to be used to handle the template is selected by {@see $viewEngineClass}.
   *
   * @var string
   */
  public $template = '';
  /**
   * The URL of an external template to be loaded and rendered.
   *
   * <p>If specified, it takes precedence over {@see $template}.
   * <p>The view engine to be used to handle the external template is selected based on the file name extension.
   *
   * @var string
   */
  public $templateUrl = '';
  /**
   * The component's view, which renders the component's appearance.
   *
   * <p>This should not be set externally.
   *
   * @var ViewInterface
   */
  public $view;
  /**
   * When true, databinding resolution on the component's view is unaffected by data from parent component's models or
   * from the shared document view model (which is set on {@see Context}); only the component's own view model is used.
   *
   * <p>TODO: this is not implemented yet.
   *
   * @var bool
   */
  protected $isolateViewModel = false;
  /**
   * A Matisse component that will be used as this component's renderable view.
   *
   * @var Component
   */
  protected $skin = null;
  /**
   * The engine to be used for parsing and rendering the view if {@see $template} is set and {@see $templateUrl} is not.
   *
   * @var string
   */
  protected $viewEngineClass = MatisseEngine::class;

  function enter ()
  {
    $this->context->getDataBinder ()->push ($this->isolateViewModel
      ? new IsolateViewModel($this->viewModel)
      : $this->viewModel);
  }

  function leave ()
  {
    $this->context->getDataBinder ()->pop ();
  }

  /**
   * Allows subclasses to generate the view's markup dinamically.
   * If not overriden, the default behaviour is to load the view from an external file, if one is defined on
   * `$templateUrl`. If not, the content of `$template` is returned, if set, otherwise no output is generated.
   *
   * > **Note:** this returns nothing; the output is sent directly to the output buffer.
   */
  protected function render ()
  {
    if ($skin = $this->getSkin ()) {
      $skin->run ();
      return;
    }
    if ($this->templateUrl) {
      $this->assertContext ();
      $this->view = $this->context->viewService->loadFromFile ($this->templateUrl);
    }
    elseif ($this->template) {
      $this->assertContext ();
      $this->view = $this->context->viewService->loadFromString ($this->template, $this->viewEngineClass);
    }
    else return;

    $this->view->compile ();
    $this->setupView ($this->view);
    echo $this->view->render ();
    $this->afterRender ($this->view);
  }

  protected function setupViewModel ()
  {
    // Defaults the view model to the component itself.
    if (!isset($this->viewModel))
      $this->viewModel = $this;

    parent::setupViewModel ();
  }

  /**
   * When the component's view is a matisse template, this returns the root of the parsed template, otherwise it returns
   * `null`.
   *
   * <p>Subclasses may override this to return a skin other than the component's default one.
   *
   * > <p>This is also used by {@see ComponentInspector} for inspecting a composite component's children.
   *
   * @return Component|null A {@see DocumentFragment}, but it may also be any other component type.
   */
  function getSkin ()
  {
    return $this->skin
      ?: ($this->view && $this->view->getEngine () instanceof MatisseEngine
        ? $this->view->getCompiled ()
        : null);
  }

  /**
   * Sets the given Matisse component as this component's renderable view.
   *
   * <p>If set, this will override {@see template} and {@see templateUrl}.
   *
   * @param Component $skin
   */
  function setSkin (Component $skin)
  {
    $this->skin = $skin;
  }

  /**
   * Allows access to the view after the page is rendered.
   *
   * <p>Override to add debug logging, for instance.
   *
   * ><p>**Note:** this will not be called for skins set via {@see setSkin}.
   *
   * @param ViewInterface $view
   */
  protected function afterRender (ViewInterface $view)
  {
    //override
  }

  /**
   * Allows access to the compiled view generated by the parsing process.
   *
   * <p>Component specific initialization can be performed here before the page is rendered.
   * Override to add extra initialization.
   *
   * @param ViewInterface $view
   */
  protected function setupView (ViewInterface $view)
  {
    $engine = $view->getEngine ();
    if ($engine instanceof MatisseEngine) {
      /** @var Component $document */
      $document = $view->getCompiled ();
      $document->attachTo ($this);
    }
  }

  private function assertContext ()
  {
    if (!$this->context)
      throw new ComponentException($this,
        sprintf ("Can't render the component's template because the rendering context is not set.
<p>See <kbd>%s</kbd>", formatClassName (RenderableInterface::class)));
  }

}
