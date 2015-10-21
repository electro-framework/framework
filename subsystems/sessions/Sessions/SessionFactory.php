<?php
namespace Selenia\Sessions;

use Selenia\Interfaces\SessionFactoryInterface;

class SessionFactory implements SessionFactoryInterface
{
  function get ()
  {
    global $session;
    return $session;
  }
}
