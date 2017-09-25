<?php

namespace Electro\Localization\Services;

use Electro\Kernel\Services\ModulesRegistry;
use Electro\Localization\Config\LocalizationSettings;

class TranslationService
{
  /**
   * @var Locale
   */
  private $locale;
  /**
   * @var LocalizationSettings
   */
  private $localizationSettings;
  /**
   * @var ModulesRegistry
   */
  private $modulesRegistry;

  function __construct (ModulesRegistry $modulesRegistry, LocalizationSettings $localizationSettings, Locale $locale)
  {
    $this->modulesRegistry = $modulesRegistry;
    $this->localizationSettings = $localizationSettings;
    $this->locale = $locale;
  }

  /**
   * Function to get translation by specific key
   *
   * @param      $key
   * @param null $localeName
   * @return mixed
   * @internal param null $locale
   */
  function get ($key,$localeName = null)
  {
    $modulesRegistry = $this->modulesRegistry;

    if (!$localeName)
      $localeName = $this->locale->locale();

    $modules = $this->getAvailableModulesOfKey($key);

    if (!$modules)
      return $key;

    sort($modules);
    $module = $modulesRegistry->getModule($modules[0]);
    $resourcesLangPath = $this->getResourcesLangPath($module)."/$localeName.ini";

    $translations = fileExists($resourcesLangPath) ? parse_ini_file($resourcesLangPath) : [];
    return get ($translations,$key);
  }

  /**
   * Function to get an array with all translations in all entire project
   */
  function getAllTranslations()
  {
    $translations = [];
    $modules = $this->getAllModulesOfProject();
    foreach ($modules as $module)
    {
      $trans = $this->getTranslationsOfModule($module);
      if ($trans)
      {
        foreach ($trans as $value)
        {
          foreach ($value as $key => $val)
          {
            if (!isset($translations[$key]))
            {
              $langsAvailable = $this->getAvailableLangsOfKey($key);
              $translations[$key] = [
                'key' => $key,
                'value' => $val,
                'module' => $module->name,
                'locale' => implode(', ', $langsAvailable)
              ];
            }
          }
        }
      }
    }
    return $translations;
  }

  /**
   * Method to get all translations of specific module
   *
   * @param $module
   * @internal param ModulesRegistry $moduleInfo
   * @return array
   */
  function getTranslationsOfModule($module)
  {
    $translations = [];
    $resourcesLangPath = $this->getResourcesLangPath($module);
    if (fileExists($resourcesLangPath))
    {
      $iniFiles = preg_grep ('~\.(ini)$~', array_diff (scandir ($resourcesLangPath), ['..', '.']));
      foreach ($iniFiles as $iniFile)
        $translations[str_replace('.ini', '', $iniFile)] = parse_ini_file ("$resourcesLangPath/$iniFile");
    }
    return $translations;
  }

  /**
   * Method to get all languages where the indicated key is registered
   * @param $key
   * @return array
   */
  function getAvailableLangsOfKey($key)
  {
    $langs = [];
    $modules = $this->getAllModulesOfProject();
    foreach ($modules as $module)
    {
      $translations = $this->getTranslationsOfModule($module);
      foreach ($translations as $lang => $value)
      {
        if (get($value,$key))
          $langs[$key][] = strtoupper($this->locale->shortCode ($lang));
      }
    }
    return $langs ? array_unique($langs[$key]) : [];
  }

  /**
   * Private method to get resources languages folder of specific module
   * @param $module
   * @return string
   */
  function getResourcesLangPath($module)
  {
    return "$module->path/{$this->localizationSettings->moduleLangPath}";
  }

  /**
   * Private method to get all modules of current project
   * @return \Electro\Kernel\Lib\ModuleInfo[]
   */
  private function getAllModulesOfProject()
  {
    return $this->modulesRegistry->getModules();
  }

  /**
   * Method to get all modules where the indicated key is registered
   *
   * @param $key
   * @return array
   */
  function getAvailableModulesOfKey($key)
  {
    $availableModules = [];
    $modules = $this->getAllModulesOfProject();
    foreach ($modules as $module)
    {
      $translations = $this->getTranslationsOfModule($module);
      foreach ($translations as $lang => $value)
      {
        if (get($value,$key) && !in_array($module->name, $availableModules))
          $availableModules[] = $module->name;
      }
    }
    return $availableModules;
  }
}
