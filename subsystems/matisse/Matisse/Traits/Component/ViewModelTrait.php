<?php
namespace Selenia\Matisse\Traits\Component;

use Selenia\ViewEngine\Lib\ViewModel;

trait ViewModelTrait
{
  /**
   * Returns the component's view model (its own or an inherited one).
   *
   * @return array|null|object
   */
  function getViewModel ()
  {
    return $this->context->getDataBinder ()->getViewModel ();
  }

  /**
   * Sets the component's view model.
   *
   * @param array|null|object $viewModel
   */
  function setViewModel ($viewModel)
  {
    $this->context->getDataBinder ()->getViewModel ($viewModel);
  }

  /**
   * Extension hook.
   *
   * @override
   */
  protected function afterPreRun ()
  {
    parent::afterPreRun ();
    $this->viewModel ($this->getViewModel ());
  }

  /**
   * Override to set data on the component's view model.
   *
   * @param ViewModel $viewModel The view model where data can be stored for later access by the view renderer.
   */
  protected function viewModel (ViewModel $viewModel)
  {
    //override
  }

}
