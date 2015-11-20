<?php
namespace Selenia\Interfaces\Navigation;

/**
 * A service that assists in generating a menu or breadcrumb navigation for the application.
 */
interface NavigationProviderInterface
{
  /**
   * @param NavigationInterface $navigation
   * @return array
   */
  function getNavigation (NavigationInterface $navigation);
}
