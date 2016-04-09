<?php
namespace Selenia\Matisse\Properties\Macro;

use Selenia\Matisse\Components\Macro\Macro;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Properties\Base\MetadataProperties;

class MacroCallProperties extends MetadataProperties
{
  /**
   * The name of the macro to be loaded at parse time and inserted on the current view, replacing the `MacroInstance`
   * component.
   *
   * > <p>You **can not** use databinding on this property, as the view model is not available at parse time.
   *
   * @var string
   */
  public $macro = '';
  /**
   * Points to the component that defines the macro for these properties.
   *
   * @var Macro
   */
  private $macroInstance;

  function getAll ()
  {
    $names = $this->getPropertyNames();
    return map ($names, function ($v, &$k) {
      $k = $v;
      return $this->$k;
    });
  }


  function __get ($name)
  {
    if (array_key_exists ($name, $this->props))
      return $this->props [$name];

    // The parameter was not set, so return the declared default value (if any).
    return $this->getDefaultValue ($name);
  }

  function defines ($name, $asSubtag = false)
  {
    if (!$this->macroInstance) $this->noMacro ();
    $this->macroInstance->getParameter ($name, $found);
    return $found;
  }

  function __set ($name, $value)
  {
    if (!$this->defines($name))
      throw new ComponentException($this->macroInstance, "Undefined parameter <kbd>$name</kbd>.");
    $this->setPropertyValue($name, $value);
  }

  function getEnumOf ($propName)
  {
    if (!$this->macroInstance) $this->noMacro ();
    return $this->macroInstance->getParameterEnum ($propName) ?: [];
  }

  function getPropertyNames ()
  {
    if (!$this->macroInstance) $this->noMacro ();
    return $this->macroInstance->getParametersNames ();
  }

  function getTypeOf ($propName)
  {
    if (!$this->macroInstance) $this->noMacro ();
    return $this->macroInstance->getParameterType ($propName);
  }

  function isEnum ($propName)
  {
    if (!$this->macroInstance) $this->noMacro ();
    return !is_null ($this->macroInstance->getParameterEnum ($propName));
  }

  function getDefaultValue ($name)
  {
    if (!$this->macroInstance)
      $this->noMacro ();
    $param = $this->macroInstance->getParameter ($name, $found);
    if (!$found)
      throw new ComponentException($this->macroInstance, "Undefined parameter <kbd>$name</kbd>.");

    //TODO: test this
    if (isset($param->bindings) && array_key_exists ('default', $param->bindings))
      return $param->bindings['default'];
    return $param->props->default; // Return the parameter's `default` property.
  }

  /**
   * Sets the component that defines the macro for these properties.
   * > This is used by {@see MacroInstance} when it creates an instance of this class.
   *
   * @param Macro $macro
   */
  function setMacro (Macro $macro)
  {
    $this->macroInstance = $macro;
  }

  private function noMacro ()
  {
    throw new ComponentException($this->component,
      "Can't access any of a macro instance's properties before a macro is assigned to it");
  }
}
