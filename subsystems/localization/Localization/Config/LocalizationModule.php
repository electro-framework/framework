<?php
namespace Selenia\Localization\Config;

use Selenia\Interfaces\DI\InjectorInterface;
use Selenia\Interfaces\DI\ServiceProviderInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Localization\Services\Locale;

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
