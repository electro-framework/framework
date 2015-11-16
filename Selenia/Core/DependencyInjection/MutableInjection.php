<?php
namespace Selenia\Core\DependencyInjection;

class MutableInjection
{
  private $value;

  function set ($value) {
    $this->value = $value;
  }

  function __invoke () {
    return $this->value;
  }
}
