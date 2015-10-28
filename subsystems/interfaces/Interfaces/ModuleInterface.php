<?php
namespace Selenia\Interfaces;

use Selenia\Core\Assembly\Services\ModuleServices;

/**
 * Denotes a class that can provide module configuration and bootstrapping.
 */
interface ModuleInterface
{
  /**
   * Allows a module to request services and perform actions upon them, prior to the HTTP request being sento to the
   * HTTP processing pipeline.
   * <p>This is called after all service providers are configured and have already registered their services
   * <h3>Injecting services</h3>
   * This is an injectable method. You can use the injected services to setup additional
   * functionality that is provided by this provider.
   * <p>To make your method implementation compatible with this interface signature, append <b>`= null`</b> to all of
   * its parameters on the method declaration.
   * > Ex:
   * ```
   * function boot (Service1 $a = null, Service2 $b = null) {}
   * ```
   * Of course, the injected services will never be `null`.
   */
  function boot ();

  /**
   * Allows a module to configure the standard module capabilities it provides.
   * <p>This is called after all service providers have registered their services.
   * @param ModuleServices $module
   * @return
   */
  function configure (ModuleServices $module);

}
