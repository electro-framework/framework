<?php
namespace Selenia\Matisse\Properties\Macro;

use Selenia\Matisse\Components\Macro\Macro;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Properties\Base\MetadataProperties;

class MacroInstanceProperties extends MetadataProperties
{
  /**
   * Points to the component that defines the macro for these properties.
   *
   * @var Macro
   */
  private $macro;

  function __get ($name)
  {
    if (array_key_exists ($name, $this->props))
      return $this->props [$name];

    // The parameter was not set, so return the declared default value (if any).
    return $this->getDefault ($name);
  }

  function defines ($name, $asSubtag = false)
  {
    if (!$this->macro) $this->noMacro ();
    return !is_null ($this->macro->getParameter ($name));
  }

  function getEnumOf ($propName)
  {
    if (!$this->macro) $this->noMacro ();
    return $this->macro->getParameterEnum ($propName) ?: [];
  }

  function getPropertyNames ()
  {
    if (!$this->macro) $this->noMacro ();
    return $this->macro->getParametersNames ();
  }

  function getTypeOf ($propName)
  {
    if (!$this->macro) $this->noMacro ();
    return $this->macro->getParameterType ($propName);
  }

  function isEnum ($propName)
  {
    if (!$this->macro) $this->noMacro ();
    return !is_null ($this->macro->getParameterEnum ($propName));
  }

  function getDefault ($name)
  {
    if (!$this->macro) $this->noMacro ();
    $param = $this->macro->getParameter ($name);
    if (is_null ($param))
      throw new ComponentException($this->macro, "Undefined parameter <kbd>$name</kbd>.");

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
    $this->macro = $macro;
  }

  private function noMacro ()
  {
    throw new ComponentException($this->component,
      "Can't access any of a macro instance's properties before a macro is assigned to it");
  }
}
