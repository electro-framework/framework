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
    if (array_key_exists($name, $this->props))
      return $this->props [$name];

    // The parameter was not set, so return the declared default value (if any).
    return $this->getDefault ($name);
  }

  function defines ($name, $asSubtag = false)
  {
    return !is_null ($this->macro->getParameter ($name));
  }

  function getEnumOf ($propName)
  {
    return $this->macro->getParameterEnum ($propName) ?: [];
  }

  function getPropertyNames ()
  {
    return $this->macro->getParametersNames ();
  }

  function getTypeOf ($propName)
  {
    return $this->macro->getParameterType ($propName);
  }

  function isEnum ($propName)
  {
    return !is_null ($this->macro->getParameterEnum ($propName));
  }

  function getDefault ($name)
  {
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

}
