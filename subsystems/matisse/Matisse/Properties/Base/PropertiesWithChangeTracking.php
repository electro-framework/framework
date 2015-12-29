<?php
namespace Selenia\Matisse\Properties\Base;

class PropertiesWithChangeTracking extends ComponentProperties
{
  /**
   * A list of names of the properties that can be set while still considering the component's properties as not begin
   * modified.
   *
   * @var string[]
   */
  static protected $NEVER_DIRTY = [];

  /**
   * Set to `true` when one or more properties have been changed from their default values, **at initialization time**.
   *
   * @var bool
   */
  protected $_modified = false;

  /**
   * Indicates if one or more properties have been changed from their default values.
   *
   * @return bool
   */
  function isModified ()
  {
    return $this->_modified;
  }

  protected function onPropertyChange ($name)
  {
    if (!isset(static::$NEVER_DIRTY[$name]))
      $this->_modified = true;
  }

}
