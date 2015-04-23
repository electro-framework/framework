<?php
namespace selene\matisse;

/**
 * Wraps any iteratable object or array so that it may function as a data source.
 */
class DataSet extends DataSource implements \IteratorAggregate
{

  protected $iterator;

  /**
   * Creates a wrapper for the specified data aggregate.
   * @param mixed $data Can be an array, an object that implements Iterator or a traversable object.
   */
  function __construct ($data = null)
  {
    if (empty($data))
      $this->iterator = new \EmptyIterator();
    else if (is_array ($data))
      $this->iterator = new \ArrayIterator($data);
    else if ($data instanceof \Iterator)
      $this->iterator = $data;
    else if ($data instanceof \Traversable)
      $this->iterator = new \IteratorIterator($data);
    else throw new \InvalidArgumentException('The specified value cannot source a DataSet.');
  }

  /**
   * Returns the data set's iterator.
   * @return \Iterator
   */
  function getIterator ()
  {
    return $this->iterator;
  }
}
