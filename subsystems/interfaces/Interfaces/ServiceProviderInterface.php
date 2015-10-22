<?php
namespace Selenia\Interfaces;

interface ServiceProviderInterface
{
  /**
   * Called after all service providers have registered their services.
   * <p>This is an injectable method. You can use the injected services to setup additional
   * functionality that is provided by this provider.
   */
  function boot ();

  /**
   * Registers services on the provided dependency injector.
   * > **Best practice:** do not use the injector to fetch dependencies here.
   * @param InjectorInterface $injector
   */
  function register (InjectorInterface $injector);

}
