<?php
namespace Selenia\Matisse\Lib;

use Selenia\Matisse\Exceptions\DataBindingException;
use Selenia\Matisse\Interfaces\DataBinderInterface;
use Selenia\Matisse\Parser\Context;
use Selenia\Traits\InspectionTrait;

/**
 * Manages the view's data-binding context.
 */
class DataBinder implements DataBinderInterface
{
  use InspectionTrait;

  static $INSPECTABLE = [
    'viewModelStack',
  ];

  /**
   * @var Context
   */
  private $context;
  /**
   * @var array|object|false False if the stack is empty.
   */
  private $viewModel = false;
  /**
   * @var array
   */
  private $viewModelStack = [];

  public function __construct (Context $context)
  {
    $this->context = $context;
  }

  function filter ($name, ...$args)
  {
    $filter = $this->context->getFilter ($name);
    return call_user_func_array ($filter, $args);
  }

  function get ($key)
  {
    if ($this->viewModel === false)
      throw new DataBindingException ("Can't data-bind on an empty view model stack.<p>" .
                                      count ($this->viewModelStack));

    $v = _g ($this->viewModel, $key, $this);
    if ($v !== $this)
      return $v;

    if (!$this->viewModel instanceof IsolateViewModel) {
      $data = $this->context->viewModel;
      if (isset($data)) {
        $v = _g ($data, $key, $this);
        if ($v !== $this)
          return $v;
      }
    }

    return null;
  }

  function getViewModel ()
  {
    return $this->viewModel;
  }

  function pop ()
  {
    if (!$this->viewModelStack)
      throw new DataBindingException ("Can't pop a view model from an empty stack
<blockquote>Proabably, a component has set its view model <b>after</b> the <kbd>setupViewModel</kbd> call.</blockquote>");
    array_pop ($this->viewModelStack);
    $this->viewModel = last ($this->viewModelStack);
//    inspect ("POP #" . count ($this->viewModelStack), shortTypeOf($this->viewModel));
  }

  function push ($viewModel)
  {
    if (is_object ($viewModel) || is_array ($viewModel))
      $this->viewModelStack[] = $this->viewModel = $viewModel;
    else throw new DataBindingException ("Only arrays and objects can be used as view models");
//    inspect ("PUSH #" . count ($this->viewModelStack), shortTypeOf($this->viewModel));
  }

  function renderBlock ($name)
  {
    return $this->context->renderBlock ($name);
  }

  function reset ()
  {
//    inspect ("RESET STACK");
    $this->viewModelStack = [];
  }

}
