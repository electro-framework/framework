<?php
namespace Selenia\Matisse\Traits\Component;

use Selenia\Classes\Overlay;
use Selenia\Matisse\Lib\DataBinder;

trait ViewModelTrait
{
  /**
   * When set, the component's view model is made available on the shared view model under the specified key name.
   *
   * @var string
   */
  protected $shareViewModelAs = null;
  /**
   * The component's view model (its own or an inherited one).
   *
   * <p>Subclasses should only set this if the respective component provides a view model.
   *
   * > <p>Do not confuse this with {@see Context::viewModel}, the later will be effective only if a field is not found
   * on any of the cascaded component view models.
   *
   * @var array|object|null
   */
  protected $viewModel = null;

  /**
   * Returns the component's view model (its own or an inherited one).
   *
   * @return array|null|object
   */
  function getViewModel ()
  {
    return $this->viewModel;
  }

  /**
   * Sets the component's view model.
   *
   * @param array|null|object $viewModel
   */
  function setViewModel ($viewModel)
  {
    $this->viewModel = $viewModel;
  }

  /**
   * Extension hook.
   *
   * @override
   */
  protected function afterPreRun ()
  {
    parent::afterPreRun ();
    $this->setupViewModel ();
  }

  /**
   * @override
   */
  protected function databind ()
  {
    $this->setupInheritedViewModel ();
    parent::databind ();
  }

  /**
   * Creates and returns an overlay over the current view model.
   *
   * @return Overlay
   */
  protected function overlayViewModel ()
  {
    return Overlay::from ($this->dataBinder ? $this->dataBinder->getViewModel () : []);
  }

  protected function setupInheritedViewModel ()
  {
    // Copy the parent's data binder if one was not explicitly set on this component.
    if ($this->parent) {
      if (!$this->dataBinder)
        $this->dataBinder = $this->parent->getDataBinder ();
    }
    else {
      if (!static::isRootComponent)
        _log ()->warning ("Rendering detached component <kbd>" . shortTypeOf ($this) . '</kbd>');
    }
  }

  /**
   * Sets up the component's view model and data binder, right before the component is rendered.
   *
   * > <p>**Note:** to set a view model, a component class should override `viewModel()`, not this method.
   */
  protected function setupViewModel ()
  {
    $this->viewModel ();

    // Note: at this point, the component's data binder will usually be the inherited one, unless there was nothing to
    // inherit.
    $binder = $this->dataBinder ?: new DataBinder($this->context);

    if (isset($this->viewModel)) {
      $binder = $binder->withViewModel ($this->viewModel);

      // Publish view model.
      if (exists ($this->shareViewModelAs))
        $this->context->viewModel[$this->shareViewModelAs] = $this->viewModel;
    }

    if ($this->dataBinder)
      $binder = $binder->withIsolation (static::isolatedViewModel);

    $this->dataBinder = $binder;

    if (!$this->dataBinder || !$this->getShadowDOM ()) return;

    // When there is a shadowDOM, transfer the data binding context to it.

    // Optimization
    if (!$this->viewModel && !static::publishProperties)
      $this->dataBinder = null;

    // Do NOT inherit the binder's view model nor its properties!
    else $this->dataBinder = $this->dataBinder
      ->withViewModel ($this->viewModel)
      ->withProps (static::publishProperties ? $this->props : null);
  }

  /**
   * Override to set data on the component's view model.
   *
   * Data will usually be set on the component instance itself.
   * <p>Ex:
   * > `$this->data = ...`
   */
  protected function viewModel ()
  {
    //override
  }

}
