<?php
namespace Selenia\Core\DependencyInjection;

/**
 * Represents an injectable service that holds a mutable value.
 *
 * <p>To inject an instance of this service, you must first create a class alias of it (using `class_alias()` and then
 * you may inject it wherever you need access to it.
 */
class MutableInjection
{
  private $value;

  function set ($value) {
    $this->value = $value;
  }

  function get () {
    return $this->value;
  }
}
