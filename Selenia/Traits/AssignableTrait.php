<?php
namespace Selenia\Traits;

trait AssignableTrait
{
  /**
   * Loads the given data into the object, including private and protected properties.
   * @param Array $data
   * @return $this For chaining.
   */
  function assign (array $data)
  {
    foreach ($data as $k => $v)
      $this->$k = $v;
    return $this;
  }

  /**
   * Exports all of object's properties, including private and protected ones.
   * @return array
   */
  function export ()
  {
    return get_object_vars ($this);
  }

}
