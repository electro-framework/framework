<?php
namespace Selenia\Localization;

use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class LocalizationModule implements ServiceProviderInterface, ModuleInterface
{
  function boot () { }

  function configure (ModuleServices $module)
  {
    $module
      ->setDefaultConfig ([
        'selenia/localization' => (new LocalizationConfig)
          ->selectionMode ('session'),
      ]);
  }

  function register (InjectorInterface $injector)
  {
    $injector->share (new Locale);
  }
}
