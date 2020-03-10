<?php
namespace Electro\Localization\Config;

use Electro\Kernel\Lib\ModuleInfo;
use Electro\Localization\Services\Locale;
use Electro\Localization\Services\TranslationService;

class LanguageSettings
{
  static function setAvailableLanguages(ModuleInfo $moduleInfo,TranslationService $translationService)
  {
    $languages = [];
    $iniFiles = $translationService->getIniFilesOfModule($moduleInfo);
    foreach ($iniFiles as $iniFile) {
      $iniFile = str_replace('.ini','',$iniFile);
      $languages[$iniFile] = $iniFile;
    }
    if (!$languages)
      return [Locale::normalize (env ('APP_DEFAULT_LANG', 'en'))];
    else
      return array_keys($languages);
  }
}
