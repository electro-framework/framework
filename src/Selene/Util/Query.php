<?php
namespace Selene\Util;

use Traversable;

class Query implements \IteratorAggregate
{
  private $data;
  private $it;

  /**
   * Sets the initial data/iterator.
   * @param array|\Iterator $src
   */
  function __construct ($src)
  {
    if ($src instanceof \Iterator)
      $this->it = $src;
    else if (is_array ($src))
      $this->data = $src;
    else throw new \InvalidArgumentException;
  }

  /**
   * @param $src
   * @return static
   */
  static function from ($src)
  {
    return new static ($src);
  }

  static function range ($from, $to, $step = 1)
  {
    return new static (new RangeIterator($from, $to, $step));
  }

  /**
   * Materializes the current iterator chain into an array.
   * @return array
   */
  function all ()
  {
    return isset($this->data) ? $this->data : iterator_to_array ($this->it);
  }

  /**
   * Calls the specified callback for each element on the collection.
   * @param callable $fn A callback that receives
   * @return $this
   */
  function each (callable $fn)
  {
    foreach ($this->getIterator () as $k => $v) $fn ($v, $k);
    return $this;
  }

  /**
   * @param callable $fn
   * @return $this
   */
  function filterAndMap (callable $fn)
  {
    if (isset($this->data)) {
      $this->it = new \ArrayIterator ($this->data);
      unset ($this->data);
    }
    $this->it = new \CallbackFilterIterator (new MapIterator($this->it, $fn), function ($v) {
      return isset($v);
    });
    return $this;
  }

  /**
   * (PHP 5 &gt;= 5.0.0)<br/>
   * Retrieve an external iterator
   * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
   * @return Traversable An instance of an object implementing <b>Iterator</b> or
   * <b>Traversable</b>
   */
  public function getIterator ()
  {
    if (isset($this->data)) {
      $this->it = new \ArrayIterator ($this->data);
      unset ($this->data);
    }
    return $this->it;
  }

  /**
   * @param callable $fn
   * @return $this
   */
  function map (callable $fn)
  {
    if (isset($this->data)) {
      $this->it = new \ArrayIterator ($this->data);
      unset ($this->data);
    }
    $this->it = new MapIterator($this->it, $fn);
    return $this;
  }

  /**
   * Reindexes the current data into a series of sequential integer values, thereby eliminating discontinuous keys.
   * @return $this
   */
  function pack ()
  {
    $this->data = isset ($this->data) ? array_values ($this->data) : iterator_to_array ($this->it, false);
    return $this;
  }

  /**
   * @param int $offset
   * @param int $count
   * @return $this
   */
  function slice ($offset = 0, $count = -1)
  {
    if (isset($this->data)) {
      $this->it = new \ArrayIterator ($this->data);
      unset ($this->data);
    }
    $this->it = new \LimitIterator($this->it, $offset, $count);
    return $this;
  }

  /**
   * @param int $flags One or more of the SORT_XXX constants.
   * @return $this
   */
  function sort ($flags)
  {
    $this->data = $this->all ();
    sort ($this->data, $flags);
    return $this;
  }

  /**
   * Wraps a recursive iterator over the current iterator.
   * @param callable $fn A callback that receives the current node's value and key and returns an iterator for the
   *                     node's children or `null` if the node has no children.
   * @return $this
   */
  function subquery (callable $fn)
  {
    $this->it = new \RecursiveIteratorIterator(new CustomRecursiveIterator($this->it, $fn));
    return $this;
  }

  /**
   * Filters data by a condition.
   * @param callable $fn A callback that receives the element and its key and returns `true` for the elements that
   *                     should be kept.
   * @return $this
   */
  function where (callable $fn)
  {
    if (isset($this->data)) {
      $this->it = new \ArrayIterator ($this->data);
      unset ($this->data);
    }
    $this->it = new \CallbackFilterIterator($this->it, $fn);
    return $this;
  }
}
