<?php
namespace Selenia\Interfaces;

/**
 * A service that assists in generating a menu or breadcrumb navigation for the application.
 */
interface NavigationProviderInterface
{
  /**
   * Invokes the callable whose key matches the next location and returns its response, or returns false if no match
   * occurred.
   * @param array $map A <kbd>[string => callable|string]</kbd> map.
   *                   <p>Each callable should have a <kbd>RouterInterface</kbd> signature:
   *                   <code>ResponseInterface|false (RouterInterface $router)</code>
   *                   If mapping to a string, it should be the name of an invokable class, which will be instantiated
   *                   trough dependency injection.
   * @return ResponseInterface|false
   */
  function getNavigation ();
}
