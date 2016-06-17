<?php
namespace Electro\Localization\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Localization\Services\Locale;

class LocalizationModule implements ServiceProviderInterface, ModuleInterface
{
  function boot (LocalizationSettings $settings = null)
  {
    if ($settings && ($tz = $settings->timeZone ()))
      date_default_timezone_set ($tz);
  }

  function register (InjectorInterface $injector)
  {
    $injector
      ->share (LocalizationSettings::class)
      ->share (new Locale, 'locale');
  }

}
