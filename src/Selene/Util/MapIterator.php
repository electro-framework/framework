<?php
namespace Selene\Util;

class MapIterator extends \IteratorIterator
{
  private $fn;

  function __construct (\Traversable $iterator, callable $mapFn)
  {
    parent::__construct ($iterator);
    $this->fn = $mapFn;
  }

  function current ()
  {
    $fn = $this->fn;
    return $fn (parent::current ());
  }
}
