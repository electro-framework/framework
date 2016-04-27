<?php
namespace Selenia\Matisse\Traits\Component;

use Selenia\Matisse\Components\Internal\DocumentFragment;
use Selenia\ViewEngine\Lib\ViewModel;

trait ViewModelTrait
{
  /**
   * Sets the component's view model.
   *
   * @param array|null|object $viewModel
   */
//  function setViewModel ($viewModel)
//  {
//    $this->context->getDataBinder ()->getViewModel ($viewModel);
//  }

  /**
   * Extension hook.
   *
   * @override
   */
  protected function afterPreRun ()
  {
    parent::afterPreRun ();
    $shadowDOM = $this->getShadowDOM ();
    if ($shadowDOM) {
      /** @var DocumentFragment $shadowDOM */
      $binder = $shadowDOM->getDataBinder ();
      if (!static::isolatedViewModel) {
        inspect ("NOT ISOLATED", $this, $shadowDOM, $this->getViewModel (), $binder->getViewModel ());
        $binder->setViewModel ($this->getViewModel ());
        inspect ($binder->getViewModel ());
      }
      $this->viewModel ($binder->getViewModel ());
      $binder->setProps ($this->props);
    }
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
