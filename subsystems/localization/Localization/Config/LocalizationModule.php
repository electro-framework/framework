<?php
namespace Electro\Localization\Config;

use Electro\Core\Assembly\ModuleInfo;
use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Localization\Services\Locale;
use const Electro\Core\Assembly\Services\CONFIGURE;
use const Electro\Core\Assembly\Services\REGISTER_SERVICES;

class LocalizationModule implements ModuleInterface
{
  static function bootUp (Bootstrapper $bootstrapper, ModuleInfo $moduleInfo)
  {
    $bootstrapper
      //
      ->on (REGISTER_SERVICES, function (InjectorInterface $injector) {
        $injector
          ->share (LocalizationSettings::class)
          ->share (new Locale, 'locale');
      })
      //
      ->on (CONFIGURE, function (LocalizationSettings $settings) {
        if ($tz = $settings->timeZone ())
          date_default_timezone_set ($tz);
      });
  }

}
