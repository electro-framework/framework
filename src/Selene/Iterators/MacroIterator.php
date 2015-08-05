<?php
namespace Selene\Iterators;

use ArrayIterator;
use Iterator;
use IteratorAggregate;
use Traversable;

/**
 * An OuterIterator implementation that allows the caller to define an inner iterator to replace and expand each item
 * of the outer iterator.
 */
class MacroIterator implements \OuterIterator
{
  /**
   * When this options is set, the resulting iteration preserves the original keys from each successive inner interator.
   * When not set, keys are auto-incremented integers starting at 0.
   */
  const USE_ORIGINAL_KEYS = 1;
  /**
   * @var int For macro iterators being iterated by other macro iterations, this indicates the recursion depth.
   *          It will be set automatically if a MacroIterator is returned as an expansion of another MacroIterator's
   *          item.
   */
  public $depth = 0;
  /** @var int */
  private $flags;
  /** @var callable */
  private $fn;
  /** @var int */
  private $index;
  /** @var Iterator */
  private $inner;
  /** @var Iterator */
  private $outer;

  /**
   * @param Traversable $outer The outer iterator.
   * @param callable    $fn    A callback that receives the current outer iterator item's value and key and returns the
   *                           corresponding inner Traversable or array.
   * @param int         $flags Iterator One of the self::XXX constants.
   */
  function __construct (Traversable $outer, callable $fn, $flags = 0)
  {
    $this->outer = $outer instanceof IteratorAggregate ? $outer->getIterator () : $outer;
    $this->fn    = $fn;
    $this->flags = $flags;
  }

  function current ()
  {
    return $this->inner->current ();
  }

  function getInnerIterator ()
  {
    return $this->inner;
  }

  function key ()
  {
    return $this->flags & self::USE_ORIGINAL_KEYS ? $this->inner->key () : $this->index;
  }

  function next ()
  {
    ++$this->index;
    $this->inner->next ();
    while (!$this->inner->valid ()) {
      $this->outer->next ();
      if (!$this->outer->valid ()) {
        $this->inner = null;
        return;
      }
      $this->nextInner ();
    }
  }

  function rewind ()
  {
    $this->index = 0;
    $this->inner = null;
    $this->outer->rewind ();
    while ($this->outer->valid ()) {
      $this->nextInner ();
      $this->inner->rewind ();
      if ($this->inner->valid ()) return;
      $this->outer->next ();
    }
  }

  function valid ()
  {
    return $this->inner && $this->inner->valid ();
  }

  protected function nextInner ()
  {
    $fn = $this->fn;
    $v  = $fn ($this->outer->current (), $this->outer->key ());
    switch (true) {
      case $v instanceof static:
        $this->inner = $v;
        $v->depth    = $this->depth + 1;
        break;
      case $v instanceof IteratorAggregate:
        $this->inner = $v->getIterator ();
        break;
      case $v instanceof Iterator:
        $this->inner = $v;
        break;
      case is_array ($v):
        $this->inner = new ArrayIterator ($v);
        break;
      default:
        throw new \InvalidArgumentException ("Invalid return type from a MacroIterator's callback.");
    }
  }
}
