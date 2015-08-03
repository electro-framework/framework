<?php
namespace Selene\Util;

class RangeIterator implements \Iterator
{
  private $cur;
  private $from;
  private $to;
  private $step;
  private $key = 0;

  function __construct ($from, $to, $step = 1)
  {
    $this->cur = $this->from = $from;
    $this->to = $to;
    $this->step = $step;
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Return the current element
   * @link http://php.net/manual/en/iterator.current.php
   * @return int
   */
  public function current ()
  {
    return $this->cur;
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Move forward to next element
   * @link http://php.net/manual/en/iterator.next.php
   */
  public function next ()
  {
     $this->cur += $this->step;
    ++$this->key;
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Return the key of the current element
   * @link http://php.net/manual/en/iterator.key.php
   * @return int
   */
  public function key ()
  {
    return $this->key;
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Checks if current position is valid
   * @link http://php.net/manual/en/iterator.valid.php
   * @return boolean `false` if there is no more data to be read.
   */
  public function valid ()
  {
    return $this->step > 0 ? $this->cur <= $this->to : $this->cur >= $this->to;
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Rewind the Iterator to the first element
   * @link http://php.net/manual/en/iterator.rewind.php
   */
  public function rewind ()
  {
    $this->key = 0;
    $this->cur = $this->from;
  }
}
