<?php
namespace Selenia\Matisse\Lib;

use Selenia\Matisse\Interfaces\ExpressionContextInterface;

/**
 * Manages the view's data-binding context.
 */
class DataBinder
{
  /**
   * @var ExpressionContextInterface[]
   */
  public $contextStack = [];

  function push (ExpressionContextInterface $context)
  {
    $this->contextStack[] = $context;
  }

  function pop ()
  {
    array_pop ($this->contextStack);
  }

  /**
   * Gets a field from the current databinding context.
   *
   * > <p>**Note:** this is meant for internal use by compiled databinding expressions.
   *
   * @param string $field
   * @return mixed
   */
  function offsetGet ($field)
  {
    $data = $this->viewModel;
    if (isset($data)) {
      $v = _g ($data, $field, $this);
      if ($v !== $this)
        return $v;
    }

    /** @var static $parent */
    $parent = $this->parent;
    if (isset($parent))
      return $parent[$field];

    $data = $this->context->viewModel;
    if (isset($data)) {
      $v = _g ($data, $field, $this);
      if ($v !== $this)
        return $v;
    }

    return null;
  }


}
