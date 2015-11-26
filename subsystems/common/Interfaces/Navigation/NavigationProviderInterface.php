<?php
namespace Selenia\Interfaces\Navigation;

/**
 * Marks a class as being able to provide navigation information for the application.
 */
interface NavigationProviderInterface
{
  /**
   * Returns a navigation tree, with a single root node (ex: the Home page).
   * @param NavigationInterface $navigation
   * @return NavigationLinkInterface
   */
  function getNavigation (NavigationInterface $navigation);
}
