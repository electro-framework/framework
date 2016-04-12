<?php
namespace Selenia\Matisse\Lib;

use Selenia\Matisse\Exceptions\DataBindingException;
use Selenia\Matisse\Interfaces\DataBinderInterface;
use Selenia\Matisse\Parser\Context;

/**
 * Manages the view's data-binding context.
 */
class DataBinder implements DataBinderInterface
{
  /**
   * @var Context
   */
  private $context;
  /**
   * @var array|object
   */
  private $scope;
  /**
   * @var array
   */
  private $scopeStack = [];

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
    if (!$this->scope)
      throw new DataBindingException ("Can't access an empty scope stack");

    $v = _g ($this->scope, $key, $this);
    if ($v !== $this)
      return $v;

    if (!$this->scope instanceof IsolateViewModel) {
      $data = $this->context->viewModel;
      if (isset($data)) {
        $v = _g ($data, $key, $this);
        if ($v !== $this)
          return $v;
      }
    }

    return null;
  }

  function pop ()
  {
    array_pop ($this->scopeStack);
    $this->scope = last ($this->scopeStack);
  }

  function push ($scope)
  {
    if (is_object ($scope) || is_array ($scope))
      $this->scopeStack[] = $this->scope = $scope;
    else throw new DataBindingException ("Only arrays and objects can be used as scopes");
  }

  function renderBlock ($name)
  {
    return $this->context->renderBlock ($name);
  }

}
