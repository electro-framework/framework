<?php
namespace Selenia\Matisse\Lib;

/**
 * Wraps a view model so that it prevents the data binder to default to the shared view model if a property is not
 * found.
 */
class IsolateViewModel implements \ArrayAccess
{
  private $viewModel;

  public function __construct ($viewModel)
  {
    $this->viewModel = $viewModel;
  }

  public function offsetExists ($offset)
  {
    return hasField ($this->viewModel, $offset);
  }

  public function offsetGet ($offset)
  {
    return getField ($this->viewModel, $offset);
  }

  public function offsetSet ($offset, $value)
  {
    return setField ($this->viewModel, $offset, $value);
  }

  public function offsetUnset ($offset)
  {
    return unsetAt ($this->viewModel, $offset);
  }

}
