<?php
namespace Selenia\Interfaces\Navigation;

interface NavigationLinkInterface
{
  /**
   * Are links to this location enabled?
   *
   * <p>If disabled, the links will be shown but will not be actionable.
   *
   * <p>Default: **`true`**
   *
   * @param bool $enabled
   * @return $this|bool
   */
  function enabled ($enabled = null);

  /**
   * The menu item's icon.
   *
   * @param string $icon A space-separated list of CSS class selectors. Ex: 'fa fa-home'
   * @return $this|string
   */
  function icon ($icon = null);

  /**
   * A unique name that identifies the link.
   *
   * <p>The ID allows you to reference the link elsewhere, for instance, when generating URLs for it.
   *
   * <p>Default: **`null`** (no ID)
   *
   * @param string $id
   * @return $this|string
   * @throws \InvalidArgumentException If any child has a duplicate ID on the current navigation tree.
   */
  function id ($id = null);

  /**
   * Indicates if the link is a group pseudo-link that was created by a {@see NavigationInterface::group()} call.
   * @return bool
   */
  function isGroup ();

  /**
   * Defines this link's child links.
   *
   * @param NavigationLinkInterface[]|\Traversable|callable $navigationMap An iterable value.
   * @return $this|NavigationLinkInterface[]|\Traversable|callable You should call <kbd>iterator ($value)</kbd> to get
   *                                                               an iterator from this return value before you can
   *                                                               use it.
   */
  function links ($navigationMap);

  /**
   * The page title.
   *
   * <p>It may be displayed:
   * - on the browser's title bar and navigation history;
   * - on menus and navigation breadcrumbs.
   *
   * <p>Default: **`''`** (no title)
   *
   * @param string $title
   * @return $this|string
   */
  function title ($title = null);

  /**
   * The link's URL.
   *
   * <p>It can be a relative path, an absolute path or a full URL address.
   *
   * <p>It is derived from the the corresponding key on the array (the navigation map) that defines the link.
   *
   * <p>This is usually the full relative URL path computed from concatenating all URL segments from the trail that
   * begins on the home/root link and ends on this one.
   *
   * <p>Example: **`'admin/users'`**
   *
   * @return string
   */
  function url ();

  /**
   * Are links to this location displayed?
   *
   * <p>If `false`, the links will not be shown on menus, but they'll still be shown in navigation breadcrumbs.
   *
   * <p>Default: **`true`**
   *
   * @param bool $visible
   * @return $this|bool
   */
  function visible ($visible = null);

  /**
   * Are links to this location displayed even if the link's URL cannot be generated due to missing route parameters?
   *
   * <p>If `true`, the link will be shown on menus, but it'll be disabled (and greyed out) until the current route
   * provides all the parameters required for generating a valid URL for this link.
   *
   * <p>Enabling this setting can used to show the user that there are more links available, even if the user cannot
   * select them until an additional selection is performed somehwere on those link's parent page.
   *
   * ###### Example
   *
   * For the following menu:
   *
   * - Authors
   *     - Books
   *     - Publications
   *
   * The children of the `Authors` menu item will only become enabled when the user selects an author on the `Authors`
   * page and the corresponding author ID becomes available on the URL.
   *
   * <p>Default: **`false`**
   *
   * @param bool $visible
   * @return $this|bool
   */
  function visibleIfUnavailable ($visible = null);

}
