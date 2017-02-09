<?php

namespace Electro\Configuration\Lib;

/**
 * Allows reading and writing keys and their values from/to a settings text file in INI format (ex: the .env file),
 * preserving comments and whitespace.
 */
class IniFile
{
  const MATCH_KEY = '/^(%s\s*=\s*)("[^"]*"|\'[^\']*\'|.*?(?=\s*(?:$|;)))/m';
  protected $path;
  private   $content = '';

  function __construct ($path)
  {
    $this->path = $path;
  }

  function __toString ()
  {
    return $this->content;
  }

  function exists ()
  {
    return fileExists ($this->path, true);
  }

  /**
   * Returns the raw value of the specified key, or NULL if the key was not found.
   *
   * @param string $key
   * @return null|string
   */
  function get ($key)
  {
    if (preg_match (sprintf (self::MATCH_KEY, $key), $this->content, $m)) {
      $v = $m[2];
      if (strlen ($v)) {
        switch ($v[0]) {
          case '"':
            return trim ($v, '"');
          case "'":
            return trim ($v, "'");
        }
        return $v;
      }
      return '';
    }
    return null;
  }

  function load ()
  {
    $this->content = loadFile ($this->path, true);
    return $this;
  }

  function save ()
  {
    file_put_contents ($this->path, $this->content, FILE_USE_INCLUDE_PATH | LOCK_EX);
    return $this;
  }

  function set ($key, $value)
  {
    if (!is_string ($value))
      throw new \InvalidArgumentException(__CLASS__ . "::set() requires string values");

    if (preg_match ('#[\n\r"\';]#', $value))
      $value = "'" . addcslashes ($value, "'") . "'";

    if (is_null ($this->get ($key))) {
      $s             = in_array (substr ($this->content, -1), ["\n", "\r"]) ? "\n" : "\n\n";
      $this->content .= "$s$key = $value" . PHP_EOL;
    }
    else $this->content = preg_replace (sprintf (self::MATCH_KEY, $key), "\$1$value", $this->content);
    return $this;
  }

}

