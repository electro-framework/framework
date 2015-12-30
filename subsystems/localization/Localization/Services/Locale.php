<?php
namespace Selenia\Localization\Services;

use RuntimeException;

class Locale
{
  private static $DEFAULTS = [
    'en' => 'en-US',
    'pt' => 'pt-PT',
  ];
  private static $LOCALES  = [
    'en-US' => ['name' => 'en-US', 'label' => 'English', 'compatibleWith' => ['en_US', 'en_US.UTF-8', 'us']],
    'pt-PT' => ['name' => 'pt-PT', 'label' => 'Português', 'compatibleWith' => ['pt_PT', 'pt_PT.UTF-8', 'ptg']],
  ];
  /**
   * @var string
   */
  public $label;
  /**
   * @var string
   */
  public $name;
  /**
   * How to automatically set the current locale.  Either 'session' or 'url'.
   *
   * @var string
   */
  public $selectionMode = 'session';
  /**
   * A list of locale names supported by the application.
   * This setting is application-specific.
   *
   * @var string[]
   */
  protected $available = [];

  /**
   * Returns a map of `$name => [...locale data...]` for all known locales.
   *
   * @return array
   */
  static function getAll ()
  {
    return self::$LOCALES;
  }

  /**
   * Checks if the locale name is valid.
   *
   * @param string $name Locale name. Either a 2 char or 5 char ISO identifier.
   * @return bool
   */
  static function isValid ($name)
  {
    if ($name = self::normalize ($name))
      return isset(self::$LOCALES[$name]);
    return false;
  }

  /**
   * Converts the locale name into a normalized form.
   * Currently, 2 char identifiers are converted to 5 char form (ex: 'en' -> 'en-US').
   *
   * @param string $name
   * @return string|null `null` if the name not valid.
   */
  static function normalize ($name)
  {
    return strlen ($name) == 2 ? get (self::$DEFAULTS, $name) : $name;
  }

  /**
   * @param string $name
   * @throws RuntimeException
   */
  private static function invalidName ($name)
  {
    throw new RuntimeException("Unsupported locale name: $name");
  }

  /**
   * Returns a map of `$name => [...locale data...]` for all locales supported by the application.
   *
   * @return array
   */
  function getAvailable ()
  {
    return map ($this->available, function ($name) { return self::$LOCALES[$name]; });
  }

  /**
   * @param string[] $names A list of locale names supported by the application.
   *                        Each is either a 2 char or 5 char ISO identifier.
   * @return $this For chaining.
   * @throws RuntimeException
   */
  function setAvailable (array $names)
  {
    $this->available = map ($names, function ($name) {
      $name = self::normalize ($name);
      if (!$name || !isset(self::$LOCALES[$name]))
        self::invalidName ($name);
      return $name;
    });
    return $this;
  }

  /**
   * Checks if the a locale name is valid.
   *
   * @param string $name Locale name. Either a 2 char or 5 char ISO identifier.
   * @return bool
   */
  function isAvailable ($name)
  {
    if ($name = self::normalize ($name))
      return in_array ($name, $this->available);
    return false;
  }

  /**
   * Sets the active locale for the application during processing of the current HTTP request.
   * > This setting is not preserved between requests.
   *
   * @param string $name Locale name. Either a 2 char or 5 char ISO identifier.
   * @return $this For chaining.
   * @throws RuntimeException
   */
  function setLocale ($name)
  {
    $data = null;
    if ($name = self::normalize ($name))
      $data = get (self::$LOCALES, $name);
    if (!$data)
      self::invalidName ($name);

    $this->name  = $data['name'];
    $this->label = $data['label'];
    setlocale (LC_ALL, $data['compatibleWith']);
    return $this;
  }

  /**
   * @param string $mode Either 'session' or 'url'.
   * @return $this
   */
  function setSelectionMode ($mode)
  {
    $this->selectionMode = $mode;
    return $this;
  }
}
