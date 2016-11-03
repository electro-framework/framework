<?php
namespace Electro\Localization\Config;

use Electro\Interfaces\AssignableInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Traits\ConfigurationTrait;

/**
 * Configuration settings for the Localization subsystem.
 *
 * @method $this|string selectionMode (string $mode = null) How to automatically set the current locale: `session|url`.
 * @method $this|string timeZone (string $name = null) If set, sets the currently active timezone. Ex: 'Europe/Lisbon'
 */
class LocalizationSettings implements AssignableInterface
{
  use ConfigurationTrait;
  /**
   * Search paths for module language files, in order of precedence.
   *
   * @var array
   */
  public $languageFolders = [];
  /**
   * The relative path of the language files' folder inside a module.
   *
   * @var string
   */
  public $moduleLangPath = 'resources/lang';
  /**
   * Enables output post-processing for keyword replacement.
   * Disable this if the app is not multi-language to speed-up page rendering.
   * Keywords syntax: $keyword
   *
   * @var bool
   */
  public $translation = false;
  /**
   * @var string
   */
  private $selectionMode = 'session';
  /**
   * @var string|null
   */
  private $timeZone = null;


  /**
   * Registers a module's language translation tables.
   *
   * <p>The translation engine is automatically enabled; the module, of course, must also contain a translations folder.
   *
   * @param ModuleInfo $moduleInfo
   * @return $this
   */
  function registerTranslations (ModuleInfo $moduleInfo)
  {
    $this->languageFolders[] = "$moduleInfo->path/{$this->moduleLangPath}";
    $this->translation       = true;
    return $this;
  }

}
