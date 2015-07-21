<?php
namespace Selene\Commands;
use Robo\Task\Composer\DumpAutoload;
use Selene\Traits\CommandAPIInterface;

/**
 * Implements the Selene Task Runner's pre-set build commands.
 */
trait ModuleCommands
{
  use CommandAPIInterface;

  /**
   * Registers a module on the application's configuration, therefore enabling it for use
   * @param string $moduleName The full name (vendor-name/module-name) of the module to be registered
   */
  function moduleRegister ($moduleName = '')
  {
    if (!$moduleName)
      $moduleName = $this->askDEFAULT ("Module name", "vendor-name/module-name");
    if (!$moduleName || !strpos ($moduleName, '/'))
      $this->error ("Invalid module name");

    $this->changeModules (function (array $modules) use ($moduleName) {
      $modules[] = $moduleName;
      return $modules;
    });

    (new DumpAutoload())->run ();

    $this->done ("Module <info>$moduleName</info> was registered");
  }

  /**
   * Removes a module from the application's configuration, therefore disabling it
   * @param string $moduleName The full name (vendor-name/module-name) of the module to be unregistered
   */
  function moduleUnregister ($moduleName = null)
  {
    if ($moduleName && !strpos ($moduleName, '/'))
      $this->error ("Invalid module name");

    $this->changeModules (
      function (array $modules) use (&$moduleName) {
        if ($moduleName) {
          $i = array_search ($moduleName, $modules);
          if ($i === false)
            $this->error ("Module $moduleName is not registered");
        }
        else {
          $i          = $this->menu ("Select a module to unregister:", $modules);
          $moduleName = $modules[$i];
        }
        array_splice ($modules, $i, 1);

        return $modules;
      }
    );

    (new DumpAutoload())->run ();

    $this->done ("Module <info>$moduleName</info> was unregistered");
  }

  private function changeModules (callable $fn)
  {
    $path = $this->app ()->configPath . "/application.ini.php";
    $ini = file_get_contents ($path);
    $ini = $this->config_modifyArrayProperty ($ini, 'modules', $c, $fn);
    if (!$c)
      $this->error ("Can't parse the configuration file. Please reformat it and make sure there is a 'modules' key");
    file_put_contents ($path, $ini);
  }

  private function config_modifyArrayProperty ($src, $key, &$count, callable $fn)
  {
    return preg_replace_callback ('/^(\s*)([\'"]' . $key . '[\'"]\s*=>\s*)(\[[^]]*])/m', function ($m) use ($fn) {
      list (, $indent, $pre, $value) = $m;
      $arr = $fn (eval("return $value;"));

      return $indent . $pre . $this->formatArray ($arr, $indent);
    }, $src, 1, $count);
  }

  private function formatArray (array $arr, $indent = '')
  {
    $o = [];
    foreach ($arr as $v)
      $o[] = "$indent  " . var_export ($v, true);

    return "[" . implode (",", $o) . "$indent]";
  }

}
