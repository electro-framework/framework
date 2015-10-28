<?php
namespace Selenia\Core\Assembly;

use Selenia\Interfaces\AssignableInterface;
use Selenia\Lib\ComposerConfigHandler;
use Selenia\Traits\AssignableTrait;

/**
 * Hold information about a module registration.
 * <p>`ModulesRegistry` holds collections of instances of this class, representing all registered subsystems, plugins
 * and project-modules.
 */
class ModuleInfo implements AssignableInterface
{
  use AssignableTrait;

  const TYPE_PLUGIN    = 'plugin';
  const TYPE_PRIVATE   = 'private';
  const TYPE_SUBSYSTEM = 'subsystem';
  const ref = __CLASS__;
  /**
   * An optional textual description (one line) of the module's purpose.
   * @var string
   */
  public $description;
  /**
   * When false, the module is ignored.
   * @var bool
   */
  public $enabled = true;
  /**
   * A Unique Identifier for the module.
   * Plugins and Project Modules have names with 'vendor-name/package-name' syntax.
   * Subsystems have names with syntax: 'module-name'.
   * @var string
   */
  public $name;
  /**
   * The file system path of the module's root folder, relative to the project's root folder.
   * @var string
   */
  public $path;
  /**
   * If set, maps `$path` to the real filesystem path. This is useful when modules are symlinked and we want to display
   * debugging paths as short paths relative to the application's root directory.
   * @var string
   */
  public $realPath;
  /**
   * The module's service provider class name or null if none.
   * @var string|null
   */
  public $serviceProvider;
  /**
   * The module type: plugin, subsystem or projectModule.
   * @var string One of the self::TYPE constants.
   */
  public $type;

  function getShortName ()
  {
    $a = explode ('/', $this->name);
    return dehyphenate (end ($a), true);
  }

  /**
   * Returns the module's parsed composer.json, if it is present.
   * @return null|ComposerConfigHandler `null` if no composer.json is available.
   */
  function getComposerConfig ()
  {
    $composerConfig = new ComposerConfigHandler("$this->path/composer.json", true);
    return $composerConfig->data ? $composerConfig : null;
  }

  /**
   * Converts a module name in `vendor-name/package-name` form to a valid PSR-4 namespace.
   * @param string $moduleName
   * @return string
   */
  static function moduleNameToNamespace ($moduleName)
  {
    $o = explode ('/', $moduleName);
    if (count ($o) != 2)
      throw new \RuntimeException ("Invalid module name");
    list ($vendor, $module) = $o;
    $namespace1 = ucfirst (dehyphenate ($vendor, true));
    $namespace2 = ucfirst (dehyphenate ($module, true));

    return "$namespace1\\$namespace2";
  }

  /**
   * Retrieve the module's PHP namespace from its composer.json (if present).
   * @param string $srcPath    The argument gets assigned the source code path associated with the found namespace.
   * @return null|string `null` if no composer.json is available.
   * @throws \Exception If the module's composer.json is not a valid module config.
   */
  function getNamespace (& $srcPath = null)
  {
    $composerConfig = $this->getComposerConfig ();
    $decls          = $composerConfig->get ("autoload.psr-4");
    $namespaces     = $decls ? array_keys ($decls) : [];
    if (count ($namespaces) != 1)
      throw new \Exception ("Invalid module configuration for '$this->name': expected a single PSR-4 namespace declaration on the module's composer.json");
    $namespace = $namespaces [0];
    $srcPath   = $decls[$namespace];
    return rtrim ($namespace, '\\');
  }

}
