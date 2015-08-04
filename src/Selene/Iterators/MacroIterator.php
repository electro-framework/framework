<?php
namespace Selene\Iterators;

use Iterator;
use Traversable;

class MacroIterator implements \OuterIterator
{
  /**
   * When this options is set, the resulting iteration preserves the original keys from each successive inner interator.
   * When not set, keys are auto-incremented integers starting at 0.
   */
  const USE_ORIGINAL_KEYS = 1;
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
   * An OuterIterator implementation that allows the caller to define an inner iterator to replace and expand each item
   * of the outer iterator.
   * @param Traversable $outer The outer iterator.
   * @param callable    $fn    A callback that receives the current outer iterator item's value and key and returns the
   *                           corresponding inner Traversable or array.
   * @param int         $flags Iterator One of the self::XXX constants.
   */
  function __construct (Traversable $outer, callable $fn, $flags = 0)
  {
    $this->outer = $outer instanceof \IteratorAggregate ? $outer->getIterator () : $outer;
    $this->fn    = $fn;
    $this->flags = $flags;
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Return the current element
   * @link http://php.net/manual/en/iterator.current.php
   * @return mixed
   */
  public function current ()
  {
    return $this->inner->current ();
  }

  /**
   * (PHP 5 &gt;= 5.1.0)<br/>
   * Returns the inner iterator for the current entry.
   * @link http://php.net/manual/en/outeriterator.getinneriterator.php
   * @return Iterator The inner iterator for the current entry.
   */
  public function getInnerIterator ()
  {
    return $this->inner;
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Return the key of the current element
   * @link http://php.net/manual/en/iterator.key.php
   * @return mixed
   */
  public function key ()
  {
    return $this->flags & self::USE_ORIGINAL_KEYS ? $this->inner->key () : $this->index;
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Move forward to next element
   * @link http://php.net/manual/en/iterator.next.php
   */
  public function next ()
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

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Rewind the Iterato to the first element
   * @link http://php.net/manual/en/iterator.rewind.php
   */
  public function rewind ()
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

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Checks if current position is valid
   * @link http://php.net/manual/en/iterator.valid.php
   * @return boolean `false` if there is no more data to be read.
   */
  public function valid ()
  {
    return $this->inner && $this->inner->valid ();
  }

  protected function nextInner ()
  {
    $fn          = $this->fn;
    $v           = $fn ($this->outer->current (), $this->outer->key ());
    $this->inner = $v instanceof \IteratorAggregate
      ? $v->getIterator ()
      : (is_array ($v) ? new \ArrayIterator ($v) : $v);
  }
}
