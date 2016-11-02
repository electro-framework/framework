<?php
namespace Electro\Navigation\Config;

use Electro\Interfaces\Navigation\NavigationProviderInterface;

/**
 * Configuration settings for the Navigation subsystem.
 **/
class NavigationSettings
{
  /**
   * All registered navigation providers.
   * <p>This will be read when the Navigation service is injected for the first time.
   * It can hold class names or instances.
   *
   * @var NavigationProviderInterface[]|string[]
   */
  private $navigationProviders = [];

  /**
   * Returns all registered navigation providers.
   *
   * @return NavigationProviderInterface[]|\string[]
   */
  function getProviders ()
  {
    return $this->navigationProviders;
  }

  /**
   * Registers a navigation provider class or instance.
   *
   * @param NavigationProviderInterface|string $provider
   */
  function registerNavigation ($provider)
  {
    $this->navigationProviders[] = $provider;
  }

}
