<?php
namespace Selene\Iterators;

/**
 * Iterates over a generated sequence of numbers.
 */
class RangeIterator implements \Iterator
{
  private $cur;
  private $from;
  private $key = 0;
  private $step;
  private $to;

  /**
   * @param int|float $from Starting value.
   * @param int|float $to   The (inclusive) limit. May be lower than `$from` if the `$step` is negative.
   * @param int|float $step Can be either positive or negative. If zero, an infinite sequence of constant values is
   *                        generated.
   */
  function __construct ($from, $to, $step = 1)
  {
    $this->cur  = $this->from = $from;
    $this->to   = $to;
    $this->step = $step;
  }

  public function current ()
  {
    return $this->cur;
  }

  public function key ()
  {
    return $this->key;
  }

  public function next ()
  {
    $this->cur += $this->step;
    ++$this->key;
  }

  public function rewind ()
  {
    $this->key = 0;
    $this->cur = $this->from;
  }

  public function valid ()
  {
    return $this->step > 0 ? $this->cur <= $this->to : $this->cur >= $this->to;
  }
}
