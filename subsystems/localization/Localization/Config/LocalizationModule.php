<?php
namespace Electro\Localization\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Kernel\Services\Loader;
use Electro\Localization\Services\Locale;
use const Electro\Kernel\Services\CONFIGURE;
use const Electro\Kernel\Services\REGISTER_SERVICES;

class LocalizationModule implements ModuleInterface
{
  static function startUp (Loader $loader, ModuleInfo $moduleInfo)
  {
    $loader
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
