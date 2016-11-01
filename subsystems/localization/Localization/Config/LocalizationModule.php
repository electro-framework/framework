<?php
namespace Electro\Localization\Config;

use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Localization\Services\Locale;

class LocalizationModule implements ModuleInterface
{
  static function boot (Bootstrapper $boot)
  {
    $boot->on (Bootstrapper::REGISTER_SERVICES, function (InjectorInterface $injector) {
      $injector
        ->share (LocalizationSettings::class)
        ->share (new Locale, 'locale');
    });

    $boot->on (Bootstrapper::CONFIGURE, function (LocalizationSettings $settings) {
      if ($tz = $settings->timeZone ())
        date_default_timezone_set ($tz);
    });
  }

}
