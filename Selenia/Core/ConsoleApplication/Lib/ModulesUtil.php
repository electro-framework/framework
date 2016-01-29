<?php
namespace Selenia\Core\ConsoleApplication\Lib;

use Selenia\Core\Assembly\Services\ModulesRegistry;
use Selenia\Core\ConsoleApplication\Services\ConsoleIO;

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
   * @param string $moduleName     A variable reference. If empty, it will be set to the selected module name.
   * @param bool   $onlyEnabled    Display only modules that are enabled.
   * @param bool   $suppressErrors Do not abort execution with an error message if the module name is not valid.
   * @return bool false if the specified module name does not match an installed module
   */
  function selectModule (& $moduleName, $onlyEnabled = false, $suppressErrors = false)
  {
    if ($moduleName) {
      if (!$this->registry->validateModuleName ($moduleName)) {
        if ($suppressErrors) return false;
        $this->io->error ("Invalid module name $moduleName. Correct syntax: vendor-name/product-name");
      }
      if (!$this->registry->isInstalled ($moduleName)) {
        if ($suppressErrors) return false;
        $this->io->error ("Module $moduleName is not installed");
      }
    }
    else {
      $modules    = $this->registry->getApplicationModuleNames ($onlyEnabled);
      if ($modules) {
        $i          = $this->io->menu ("Select a module:", $modules);
        if ($i < 0) $this->io->cancel();
        $moduleName = $modules[$i];
      }
      else {
        if ($suppressErrors) return false;
        $this->io->error ("No modules are installed");
      }
    }
    return true;
  }

}
