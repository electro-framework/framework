<?php
namespace Selene\Lib;

class ApplicationConfigHandler
{
  /** @var string */
  public $data;
  /** @var  string */
  protected $path;

  function __construct ($iniFilePath = null)
  {
    global $application;

    $this->path = $iniFilePath ?: "$application->configPath/$application->configFilename";
    $config     = file_get_contents ($this->path);
    if (!$config)
      throw new \RuntimeException("$this->path was not found");
  }

  function save ()
  {
    file_put_contents ($this->path, $this->data);
  }

  function changeRegisteredModules (callable $fn)
  {
    $this->modifyArrayProperty ('modules', $fn);

    return $this;
  }

  //--------------------------------------------------------------------------------------------------------------------

  private function modifyArrayProperty ($key, callable $fn)
  {
    $this->data = preg_replace_callback ('/^(\s*)([\'"]' . $key . '[\'"]\s*=>\s*)(\[[^]]*])/m',
      function ($m) use ($fn) {
        list (, $indent, $pre, $value) = $m;
        $arr = $fn (eval("return $value;"));

        return $indent . $pre . $this->formatArray ($arr, $indent);
      },
      $this->data, 1, $count);
    if (!$count)
      throw new \RuntimeException ("Can't parse the configuration file. Please reformat it and make sure there is a '$key' key");
  }

  private function formatArray (array $arr, $indent = '')
  {
    $o = [];
    foreach ($arr as $v)
      $o[] = "$indent  " . var_export ($v, true);

    return "[" . implode (",", $o) . "$indent]";
  }


}
