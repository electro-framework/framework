<?php
namespace Selenia\Matisse\Lib;

use PhpKit\WebConsole\Lib\Debug;
use Selenia\Interfaces\CustomInspectionInterface;
use Selenia\Interfaces\RenderableInterface;
use Selenia\Matisse\Interfaces\DataBinderInterface;
use Selenia\Matisse\Parser\DocumentContext;
use Selenia\Matisse\Properties\Base\AbstractProperties;

/**
 * Manages the view's data-binding context.
 *
 * <p>Instances of this class are immutable.
 */
class DataBinder implements DataBinderInterface, CustomInspectionInterface
{
  /**
   * @var DocumentContext
   */
  private $context;
  private $isolatedViewModel = false;
  /**
   * @var AbstractProperties|null
   */
  private $props;
  /**
   * @var object|array
   */
  private $viewModel;

  /**
   * @param DocumentContext         $context
   * @param object|array|null       $viewModel [optional] A view model reference.
   * @param AbstractProperties|null $props     [optional] 
   */
  public function __construct (DocumentContext $context, &$viewModel = null, AbstractProperties $props = null)
  {
    $this->context   = $context;
    $this->viewModel =& $viewModel;
    $this->props     = $props;
  }

  function filter ($name, ...$args)
  {
    $filter = $this->context->getFilter ($name);
    return call_user_func_array ($filter, $args);
  }

  function get ($key)
  {
    $v = _g ($this->viewModel, $key, $this);
    if ($v !== $this)
      return $v;

    if (!$this->isolatedViewModel) {
      $data = $this->context->getViewModel ();
      if (isset($data)) {
        $v = _g ($data, $key, $this);
        if ($v !== $this)
          return $v;
      }
    }

    return null;
  }

  function getProps ()
  {
    return $this->props;
  }

  function &getViewModel ()
  {
    return $this->viewModel;
  }

  function inspect ()
  {
    return Debug::grid ([
      "View Model" => $this->viewModel,
      "Properties" => Debug::RAW_TEXT .
                      Debug::grid ($this->props, Debug::getType ($this->props), 1, ['props', 'component', 'hidden'],
                        true),
      "Isolation"  => $this->isolatedViewModel,
    ]);
  }

  function prop ($key)
  {
    if (!$this->props) return null;
    $v = $this->props->getComputed ($key);
    if ($v && $v instanceof RenderableInterface)
      return $v->getRendering ();
    return $v;
  }

  function renderBlock ($name)
  {
    return $this->context->getBlocksService ()->getBlock ($name)->render ();
  }

  function withIsolation ($isolated = true)
  {
    if ($isolated == $this->isolatedViewModel)
      return $this;
    $o                    = clone $this;
    $o->isolatedViewModel = $isolated;
    return $o;
  }

  function withProps (AbstractProperties $props = null)
  {
    if ($props === $this->props)
      return $this;
    $o        = clone $this;
    $o->props = $props;
    return $o;
  }

  function withViewModel (&$viewModel)
  {
    if ($viewModel === $this->viewModel)
      return $this;
    $o            = clone $this;
    $o->viewModel =& $viewModel;
    return $o;
  }

}
