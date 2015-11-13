<?php
namespace Selenia\Interfaces;

/**
 * A service that assists in generating a menu or breadcrumb navigation for the application.
 */
interface NavigationProviderInterface
{
  /**
   * @return NavigationInterface
   */
  function getNavigation ();
}
