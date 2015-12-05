<?php
namespace Selenia\Interfaces\Navigation;

use SplObjectStorage;

/**
 * Represents a set of navigation link trees and provides an API for performing operations on it.
 *
 * <p>It also provides functionality for generating links, menus and breadcrumb navigations.
 *
 * <p>There is, usually, one single instance of this interface for the whole application, unless you need to generate
 * additional menus.
 * <p>Modules may provide their own navigation maps that will be merged into the shared navigation instance.<br>
 * The shared instance will be responsible for generating the application's main menu, links and breadcrumbs.
 */
interface NavigationInterface extends \IteratorAggregate
{
  /**
   * Inserts a navigation map onto the root level of this navigation.
   *
   * @param NavigationLinkInterface[]|\Traversable|callable $navigationMap An iterable value.
   * @return $this
   * @throws \InvalidArgumentException If the argument is not iterable.
   */
  function add ($navigationMap);

  /**
   * Builds/updates the path of {@see NavigationLinkInterface} instances for the given relative URL path.
   * @param string $url
   * @return $this
   */
  function buildPath ($url);

  /**
   * A linear sequence of {@see NavigationLinkInterface} objects that represents the path to the currently displayed
   * page starting from a root (home) link.
   *
   * <p>The set can be enumerated as a list of objects, with sequential integer keys.
   * > **Note:** no `SplObjectStorage` extra data is associated with elements on the set.
   *
   * <p>All objects on the path are also on the navigation tree.
   *
   * <p>This method also allows you to test if a link on the tree is also on the path (for instance, for deciding if a
   * link on a menu is selected). Use {@see SplObjectStorage::contains()} to check for that.
   *
   * @param SplObjectStorage|null $path
   * @return $this|SplObjectStorage
   */
  function currentTrail (SplObjectStorage $path = null);

  /**
   * Returns a set of registered IDs and their corresponding links.
   *
   * <p>IDs are registered automatically when a call to `setId()` is done on a link generated from this instance using
   * {@see link()}.
   *
   * @return NavigationLinkInterface[] A map of ID => NavigationLinkInterface
   */
  function getIds ();

  /**
   * Returns a linear list of all links that are relevant to build a menu.
   * @return NavigationLinkInterface[]
   */
  function getTree ();

  /**
   * Creates a new navigation group object, bound to this Navigation.
   *
   * <p>It can be used to group child links on a menu or to display a organizational pseudo-link on a breadcrumb.
   *
   * <p>A group, when displayed as a hyperlink, has a no-action URL, so the browser does not navigate when the user
   * clicks it.
   *
   * @return NavigationLinkInterface
   */
  function group ();

  /**
   * Inserts a navigation map into this navigation, as children of a specific link.
   *
   * @param string                                          $targetId      The ID of the link where the merge will be
   *                                                                       performed.
   * @param NavigationLinkInterface[]|\Traversable|callable $navigationMap An iterable value.
   * @return $this
   * @throws
   */
  function insertInto ($targetId, $navigationMap);

  /**
   * Creates a new navigation link object, bound to this Navigation.
   * @return NavigationLinkInterface
   */
  function link ();

}
