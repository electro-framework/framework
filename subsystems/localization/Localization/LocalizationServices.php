<?php
namespace Selenia\Localization;

use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\ServiceProviderInterface;

class LocalizationServices implements ServiceProviderInterface, ModuleInterface
{
  function boot () { }

  function configure (ModuleServices $module)
  {
    $module
      ->setDefaultConfig ([
        'config' => [
          'selenia/localization' => (new LocalizationConfig)
            ->selectionMode ('session'),
        ],
      ]);
  }

  function register (InjectorInterface $injector)
  {
    $injector->share (new Locale);
  }
}
