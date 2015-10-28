<?php
namespace Selenia\Localization\Config;

use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\ServiceProviderInterface;
use Selenia\Localization\Services\Locale;

class LocalizationModule implements ServiceProviderInterface, ModuleInterface
{
  function boot () { }

  function configure (ModuleServices $module)
  {
    $module
      ->setDefaultConfig ([
        'selenia/localization' => (new LocalizationSettings)
          ->selectionMode ('session'),
      ]);
  }

  function register (InjectorInterface $injector)
  {
    $injector->share (new Locale);
  }
}
