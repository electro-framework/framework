<?php
namespace Selenia\Localization;

use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class LocalizationServices implements ServiceProviderInterface
{
  function boot () { }

  function register (InjectorInterface $injector)
  {
    $injector
      ->share (new Locale);

    ModuleOptions (dirname (__DIR__), [
      'config' => [
        'selenia/localization' => (new LocalizationConfig)
          ->selectionMode ('session')
      ],
    ]);
  }

}
