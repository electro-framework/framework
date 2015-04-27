<?php
namespace Selene\Matisse;

/**
 * A wrapper class for a data source containing a single record.
 */
class DataRecord extends DataSource implements \IteratorAggregate
{

  protected $iterator;

  /**
   * Creates a wrapper for the specified data.
   * @param mixed $source A Traversable object or an array.
   */
  function __construct ($source)
  {
    $this->iterator = new \ArrayIterator([$source]);
  }

  /**
   * Returns the data set's iterator.
   * @return \IteratorIterator
   */
  function getIterator ()
  {
    return $this->iterator;
  }
}
