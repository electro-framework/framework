<?php
namespace Selenia\Interfaces\Navigation;

interface NavigationLinkInterface
{
  /**
   * Are links to this location enabled?
   *
   * <p>If disabled, the links will be shown but will not be actionable.
   *
   * @param bool $enabled
   * @return $this|bool
   */
  function enabled ($enabled = null);

  /**
   * The menu item's icon.
   * @param string $icon A space-separated list of CSS class selectors. Ex: 'fa fa-home'
   * @return $this|string
   */
  function icon ($icon = null);

  /**
   * Defines this location's children locations.
   * @param NavigationLinkInterface[] $next An array of <kbd>[string => NavigationLinkInterface]</kbd>
   * @return $this|NavigationLinkInterface[]
   */
  function next (array $next);

  /**
   * The page title.
   *
   * <p>It may be displayed:
   * - on the browser's title bar and navigation history;
   * - on menus and navigation breadcrumbs.
   *
   * @param string $title
   * @return $this|string
   */
  function title ($title = null);

  /**
   * The relative URL path for the location this Navigation refers to.
   * <p>You do not usually need to set this property as it is automatically set from the keys of the array
   * that holds the Naviations. Nevertheless, you may also set this to a completely different URL.
   *
   * > Ex: `'admin/users'`
   *
   * @param string $url
   * @return $this|string
   */
  function url ($url = null);

  /**
   * Are links to this location displayed?
   *
   * <p>If `false`, the links will not be shown on menus, but they'll still be shown in navigation breadcrumbs.
   *
   * @param bool $visible
   * @return $this|bool
   */
  function visible ($visible = null);
}
