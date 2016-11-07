<?php
namespace Electro\Kernel\Lib;

use Electro\Interfaces\AssignableInterface;
use Electro\Lib\ComposerConfigHandler;
use Electro\Traits\AssignableTrait;

/**
 * Hold information about a module registration.
 * <p>`ModulesRegistry` holds collections of instances of this class, representing all registered subsystems, plugins
 * and project-modules.
 */
class ModuleInfo implements AssignableInterface
{
  use AssignableTrait;

  /**
   * A sprintf-compatible formatting expression, where %s = module's short name.
   */
  const BOOTSTRAPPER_CLASS_NAME_FMT = 'Config\\%sModule';
  const KEEP_PROPS                  = ['enabled'];
  const TYPE_PLUGIN                 = 'plugin';
  const TYPE_PRIORITY = [
    ModuleInfo::TYPE_SUBSYSTEM => 1,
    ModuleInfo::TYPE_PLUGIN    => 2,
    ModuleInfo::TYPE_PRIVATE   => 3,
  ];
  const TYPE_PRIVATE                = 'private';
  const TYPE_SUBSYSTEM              = 'subsystem';
  /**
   * The module's service provider class name or null if none.
   *
   * @var string|null
   */
  public $bootstrapper;
  /**
   * List of names of modules on whom this module depends on.
   *
   * @var string[]
   */
  public $dependencies = [];
  /**
   * List of names of modules that depend on this module.
   *
   * @var string[]
   */
  public $requiredBy = [];
  /**
   * An optional textual description (one line) of the module's purpose.
   *
   * @var string
   */
  public $description = '';
  /**
   * When false, the module is ignored.
   *
   * @var bool
   */
  public $enabled = true;
  /**
   * If set, the module has been disabled because it could not be loaded. This property holds the error message.
   *
   * @var string
   */
  public $errorStatus;
  /**
   * A Unique Identifier for the module.
   * Plugins and Project Modules have names with 'vendor-name/package-name' syntax.
   * Subsystems have names with syntax: 'module-name'.
   *
   * @var string
   */
  public $name;
  /**
   * The file system path of the module's root folder, relative to the project's root folder.
   *
   * @var string
   */
  public $path;
  /**
   * If set, maps `$path` to the real filesystem path. This is useful when modules are symlinked and we want to display
   * debugging paths as short paths relative to the application's root directory.
   * <p>If the module is not symlinked, this value should be null to enhance performance and to allow deployment of
   * the module configuration cache via FTP to a server where it can't be regenerated.
   *
   * @var string
   */
  public $realPath;
  /**
   * The module type: plugin, subsystem or projectModule.
   * <p>One of the `self::TYPE` constants.
   *
   * @var string
   */
  public $type;
  /**
   * @var ComposerConfigHandler Caches the module's Composer configuration.
   */
  private $composerConfig;

  /**
   * Converts a module name in `vendor-name/package-name` form to a valid PSR-4 namespace.
   *
   * @param string $moduleName
   * @return string
   */
  static function moduleNameToNamespace ($moduleName)
  {
    $o = explode ('/', $moduleName);
    if (count ($o) != 2)
      throw new \RuntimeException ("Invalid module name");
    list ($vendor, $module) = $o;
    $namespace1 = ucfirst (str_dehyphenate ($vendor, true));
    $namespace2 = ucfirst (str_dehyphenate ($module, true));

    return "$namespace1\\$namespace2";
  }

  /**
   * Returns this module's bootstrapper class name.
   *
   * @return string
   */
  function getBootstrapperClass ()
  {
    return sprintf (self::BOOTSTRAPPER_CLASS_NAME_FMT, $this->getShortName ());
  }

  /**
   * Returns the module's parsed composer.json, if it is present.
   *
   * @return null|ComposerConfigHandler `null` if no composer.json is available.
   */
  function getComposerConfig ()
  {
    if (!$this->composerConfig)
      $this->composerConfig = new ComposerConfigHandler("$this->path/composer.json", true);
    return $this->composerConfig->data ? $this->composerConfig : null;
  }

  /**
   * Retrieve the module's PHP namespace from its composer.json (if present).
   *
   * @param string $srcPath The argument gets assigned the source code path associated with the found namespace.
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

  /**
   * Returns the names of all packages required by the module.
   *
   * @return string[]
   */
  function getRequiredPackages ()
  {
    return array_diff (array_keys ($this->getComposerConfig ()->get ('require', [])), ['php']);
  }

  function getShortName ()
  {
    $a = explode ('/', $this->name);
    return str_dehyphenate (end ($a), true);
  }

}
