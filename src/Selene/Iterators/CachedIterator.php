<?php
namespace Selene\Iterators;

use ArrayIterator;

class CachedIterator extends \IteratorIterator
{
  /** @var bool */
  protected $cached = false;
  /** @var array */
  protected $data;
  /** @var ArrayIterator */
  protected $it;

  public function current ()
  {
    return $this->cached ? $this->it->current () : ($this->data[] = parent::current ());
  }

  public function getInnerIterator ()
  {
    return $this->cached ? $this->it : parent::getInnerIterator ();
  }

  public function key ()
  {
    return $this->cached ? $this->it->key () : parent::key ();
  }

  public function next ()
  {
    if ($this->cached)
      $this->it->next ();
    else parent::next ();
  }

  public function rewind ()
  {
    if (isset($this->data)) {
      // First iteration has been done.
      $this->cached = true;
      if (isset($this->it))   // Iterating for the third (or later) time: just reuse the current array iterator.
        $this->it->rewind ();
      // Iterating for the second time: create an array iterator for the cached data.
      else $this->it = new ArrayIterator($this->data);
    }
    else {
      // Iterating for the first time. Start recording data.
      $this->data = [];
      parent::rewind ();
    }
  }

  public function valid ()
  {
    return $this->cached ? $this->it->valid () : parent::valid ();
  }

}
