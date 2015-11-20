<?php
namespace Selenia\Routing\Navigation;

use Selenia\Interfaces\Navigation\NavigationInterface;

class Navigation implements NavigationInterface
{
  function link ()
  {
    return new NavigationLink;
  }
}
