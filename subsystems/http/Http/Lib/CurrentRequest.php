<?php

namespace Electro\Http\Lib;

use Electro\Interfaces\Http\Shared\CurrentRequestInterface;
use Psr\Http\Message\ServerRequestInterface;

class CurrentRequest implements CurrentRequestInterface
{
  private $instance = null;

  function get ()
  {
    return $this->instance;
  }

  function set (ServerRequestInterface $req)
  {
    $this->instance = $req;
  }

}
