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
    $boot->on (Bootstrapper::EVENT_BOOT, function (InjectorInterface $injector, LocalizationSettings $settings = null) {
      $injector
        ->share (LocalizationSettings::class)
        ->share (new Locale, 'locale');
      if ($settings && ($tz = $settings->timeZone ()))
        date_default_timezone_set ($tz);
    });
  }

}
