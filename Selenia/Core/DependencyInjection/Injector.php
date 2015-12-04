<?php
namespace Selenia\Core\DependencyInjection;

use Auryn\Injector as Auryn;
use Selenia\Interfaces\InjectorInterface;

class Injector extends Auryn implements InjectorInterface
{
  function provides ($name)
  {
    $r = $this->inspect (strtolower ($name), Injector::I_ALIASES | Injector::I_DELEGATES | Injector::I_SHARES);
    return !empty(array_filter ($r));
  }

}
