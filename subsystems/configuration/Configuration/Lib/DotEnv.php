<?php
namespace Electro\Configuration\Lib;

use Electro\Exceptions\Fatal\ConfigException;

class DotEnv
{
  /** @var string */
  private $envFile;

  public function __construct ($file)
  {
    $this->envFile = $file;
  }

  public function load ()
  {
    if (file_exists ($this->envFile)) {
      $ini = @parse_ini_file ($this->envFile, false, INI_SCANNER_TYPED);
      if ($ini)
        $this->loadConfig ($ini);
      else {
        $e = error_get_last ();
        throw new ConfigException(isset($e['message']) ? $e['message'] : "Can't load file $this->envFile");
      }
    }
  }

  public function loadConfig (array $config)
  {
    global $__ENV; // Used by env() to override the value of getenv()
    $o = [];
    foreach ($config as $k => $v) {
      $r = "REDIRECT_$k";
      if (isset($_SERVER[$r]))
        $e = $_SERVER[$r];
      else $e = getenv ($k);

      // Environment variables override .env variables
      if ($e !== false)
        $v = $e;
      else $v = trim ($v); // bevause INI_SCANNER_RAW mode keeps irrelevant whitespace on values

      $o[$k] = $v;
    }
    $__ENV = $o;
  }

}
