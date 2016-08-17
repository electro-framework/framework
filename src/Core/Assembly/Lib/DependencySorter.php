<?php
namespace Electro\Core\Assembly\Lib;

use Electro\Core\Assembly\ModuleInfo;

/**
 * Sorts modules by the order they should be loaded, according to their dependencies.
 */
class DependencySorter
{
  /**
   * Sorts modules by the order they should be loaded, according to their dependencies.
   *
   * @param ModuleInfo[] $modules
   */
  public static function sort (array &$modules)
  {
    uasort ($modules, [DependencySorter::class, "compare"]);
  }

  /**
   * Array sorting callback.
   *
   * @param ModuleInfo $a
   * @param ModuleInfo $b
   * @return int
   */
  private static function compare (ModuleInfo $a, ModuleInfo $b)
  {
    $typeSortOrder = ModuleInfo::TYPE_PRIORITY;
    if ($typeSortOrder == null)
      throw new \RuntimeException ("Priority order not defined for type module types");

    if ($a->type != $b->type)//sort by type
    {
      //make sure the priorities are defined
      if (!isset($typeSortOrder[$a->type]))
        throw new \RuntimeException ("Priority order not defined for type '{$a->type}' of module {$a->name}");
      if (!isset($typeSortOrder[$b->type]))
        throw new \RuntimeException ("Priority order not defined for type '{$b->type}' of module {$b->name}");

      //sort by the type priority
      $aindex = $typeSortOrder[$a->type];
      $bindex = $typeSortOrder[$b->type];
      return $aindex - $bindex;
    }
    else //if of same type, must check dependencies
    {
      $aDependsOnb = $a->dependencies && in_array ($b->name, $a->dependencies);
      $bDependsOna = $b->dependencies && in_array ($a->name, $b->dependencies);

      if ($aDependsOnb != $bDependsOna)//simple dependency
        return $bDependsOna ? -1 : 1;

      //no dependency or circular dependency simply sort by name;
      return strcmp ($a->name, $b->name);
    }
  }

}
