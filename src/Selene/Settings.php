<?php
namespace Selene;

class Settings
{
  public $_ = [];
  public $_type;

  function __construct ($type = '')
  {
    $this->_type = $type;
  }

  function __call ($p, $a)
  {
    $c = count ($a);

    $this->_[$p] = $c < 2 ? (!$c ?: $a[0]) : $a;

    return $this;
  }

}