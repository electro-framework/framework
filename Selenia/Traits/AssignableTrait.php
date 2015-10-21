<?php
namespace Selenia\Traits;

trait AssignableTrait
{
  /**
   * Loads the given data into the object, including private and protected properties.
   * @param Array $data
   */
  function assign (array $data)
  {
    extend ($this, $data);
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
