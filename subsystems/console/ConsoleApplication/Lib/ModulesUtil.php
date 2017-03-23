<?php

namespace Electro\ConsoleApplication\Lib;

use Electro\ConsoleApplication\Services\ConsoleIO;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Kernel\Services\ModulesRegistry;

/**
 * Utilitary shared functions for working with modules from the console.
 */
class ModulesUtil
{
  /**
   * @var ConsoleIO
   */
  private $io;
  /**
   * @var ModulesRegistry
   */
  private $registry;

  function __construct (ConsoleIO $io, ModulesRegistry $registry)
  {
    $this->io       = $io;
    $this->registry = $registry;
  }

  /**
   * Validate the given module name or ask the user to select a module from a list of installed modules.
   *
   * <p>This method is available to console tasks only.
   *
   * @param string   $moduleName       A variable reference. If empty, it will be set to the selected module name.
   * @param callable $filter           Display only modules that match a filtering condition.
   *                                   <p>Callback syntax: <code>function (ModuleInfo $module):bool</code>
   * @param bool     $suppressErrors   Do not abort execution with an error message if the module name is not valid.
   * @param callable $secondColMapper  [optional] If given, a function that receives a module name and should return
   *                                   text be displayed next to that module name on the menu, on a second column.
   * @return bool false if the specified module name does not match an installed module
   */
  function selectInstalledModule (& $moduleName, callable $filter = null, $suppressErrors = false,
                                  callable $secondColMapper = null)
  {
    return $this->selectModule ($moduleName, $this->registry->onlyPrivateOrPlugins ()->only ($filter)->getModules (),
      $suppressErrors, $secondColMapper);
  }

  /**
   * Validate the given module name or ask the user to select a module from the given list of modules.
   *
   * <p>This method is available to console tasks only.
   *
   * @param string       $moduleName
   * @param ModuleInfo[] $modules         The set of allowable modules
   * @param bool         $suppressErrors  Do not abort execution with an error message if the module name is not valid.
   * @param callable     $secondColMapper [optional] If given, a function that receives a module name and should return
   *                                      text be displayed next to that module name on the menu, on a second column.
   * @return bool false if the specified module name does not match one of the eligible modules
   */
  function selectModule (& $moduleName, array $modules, $suppressErrors = false, callable $secondColMapper = null)
  {
    if ($moduleName) {
      if (!ModulesRegistry::validateModuleName ($moduleName)) {
        if ($suppressErrors) return false;
        $this->io->error ("Invalid module name $moduleName. Correct syntax: vendor-name/product-name");
      }
      if (!$this->registry->isInstalled ($moduleName)) {
        if ($suppressErrors) return false;
        $this->io->error ("Module $moduleName is not installed");
      }
      if (!isset ($modules[$moduleName])) {
        if ($suppressErrors) return false;
        $this->io->error ("$moduleName is not a valid module name for this operation");
      }
    }
    else {
      if ($modules) {
        $moduleNames = array_keys ($modules);
        $col2        = $secondColMapper ? map ($moduleNames, $secondColMapper) : null;
        $i           = $this->io->menu ("Select a module:", $moduleNames, -1, $col2);
        if ($i < 0) $this->io->cancel ();
        $moduleName = $moduleNames[$i];
      }
      else {
        if ($suppressErrors) return false;
        $this->io->error ("No modules are available");
      }
    }
    return true;
  }

}
