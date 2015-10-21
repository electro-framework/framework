<?php
namespace Selenia\Interfaces;

interface AssignableInterface
{
  /**
   * Loads the given data into the object, including private and protected properties.
   * @param Array $data
   * @return $this For chaining.
   */
  function assign (array $data);

  /**
   * Exports all of object's properties, including private and protected ones.
   * @return array
   */
  function export ();

}
