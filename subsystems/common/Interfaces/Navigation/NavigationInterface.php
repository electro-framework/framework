<?php
namespace Selenia\Interfaces\Navigation;

use SplObjectStorage;

/**
 * A service that assists in generating menus, breadcrumb navigations and links generation.
 */
interface NavigationInterface
{
  /**
   * Builds/updates the path of {ßee NavigationLinkInterface} instances for the given relative URL path.
   * @param string $url
   * @return $this
   */
  function buildPath ($url);

  /**
   * A linear sequence of {@see NavigationLinkInterface} objects that represents the path to the currently displayed
   * page starting from a root (home) link.
   *
   * <p>All objects on the path are also on the navigation tree.
   *
   * <p>This also allows one to test if a link on the tree is also on the path (for instance, for deciding if a link
   * on a menu is selected).
   *
   * <p>There can be multiple root links, but usually, an application defines a single root link ('/').
   *
   * @param SplObjectStorage|null $path
   * @return $this|SplObjectStorage
   */
  function currentPath (SplObjectStorage $path = null);

  /**
   * Creates a new navigation link object, bound to this Navigation.
   * @return NavigationLinkInterface
   */
  function link ();

  /**
   * Mounts a navigation tree on this navigation.
   *
   * @param NavigationLinkInterface $link The tree's root link.
   * @return $this
   */
  function mount (NavigationLinkInterface $link);

  /**
   * Returns a set of registered IDs for the links tree.
   *
   * @return NavigationLinkInterface[] A map of ID => NavigationLinkInterface
   */
  function getIds ();

  /**
   * Returns a list of the root links for this navigation set.
   * @return NavigationLinkInterface[]
   */
  function getTree ();
}
