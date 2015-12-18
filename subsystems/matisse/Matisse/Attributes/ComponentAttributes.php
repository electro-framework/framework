<?php
namespace Selenia\Matisse\Attributes;

use Selenia\Matisse\Type;
use Selenia\Matisse\Base\Text;
use Selenia\Matisse\Component;
use Selenia\Matisse\Components\Parameter;
use Selenia\Matisse\Exceptions\ComponentException;

class ComponentAttributes
{
  /**
   * An array of names for each attribute data type.
   * Its public to allow access from the Macro class.
   * @var array
   */
  public static    $TYPE_NAMES     = [
    'undefined', 'identifier', 'text', 'number', 'boolean', 'parameters', 'source', 'data', 'binding', 'metadata',
  ];
  protected static $BOOLEAN_VALUES = [
    0       => false,
    1       => true,
    'false' => false,
    'true'  => true,
    'no'    => false,
    'yes'   => true,
    'off'   => false,
    'on'    => true,
  ];
  protected static $NEVER_DIRTY    = [];

  /**
   * Set to `true` when one or more attributes have been changed from their default values.
   * @var bool
   */
  public $_modified = false;
  /**
   * The component that owns these attributes.
   * @var Component
   */
  protected $component;

  public function __construct ($component)
  {
    $this->component = $component;
  }

  public static function getBoolean ($mixed)
  {
    if (is_bool ($mixed))
      return $mixed;
    if (is_string ($mixed) && array_key_exists ($mixed, self::$BOOLEAN_VALUES))
      return self::$BOOLEAN_VALUES[$mixed];
    return !is_null ($mixed) && !empty($mixed);
  }

  public static function getTypeIdOf ($typeName)
  {
    return array_search ($typeName, self::$TYPE_NAMES);
  }

  public static function validateScalar ($type, $v)
  {
    if (isset($v) && $v !== '') {
      switch ($type) {
        case Type::BOOL:
          return self::getBoolean ($v);
        //throw new InvalidArgumentException("<b>$v</b> (PHP type ".gettype($v).") is not a valid <b>boolean</b> value.");
        case Type::ID:
          if (preg_match ('#^\w+$#', $v) === false)
            throw new \InvalidArgumentException("<b>$v</b> (PHP type " . gettype ($v) .
                                                ") is not a valid <b>identifier</b>.");
          return $v;
        case Type::NUM:
          if (is_numeric ($v)) return intval ($v);
          throw new \InvalidArgumentException("<b>$v</b> (PHP type " . gettype ($v) .
                                              ") is not a valid <b>number</b>.");
        case Type::TEXT:
          if (!is_scalar ($v))
            throw new \InvalidArgumentException("A value of PHP type <b>" . gettype ($v) .
                                                "</b> is not valid for a <b>text</b> attribute/parameter.");
          if (!is_string ($v))
            return $v; //for mixed value attributes
          $v = preg_replace ('#<br ?/?>$|<p>&nbsp;</p>#', '', $v);
          $v = preg_replace ('#&nbsp;</p>#', '</p>', $v);
          return $v;
        case Type::DATA:
          if ($v instanceof \Iterator)
            return $v;
          if ($v instanceof \IteratorAggregate)
            return $v->getIterator ();
          if (is_string ($v) && strpos ($v, '{') !== false)
            return $v;
          if (is_array ($v) || is_object ($v))
            return $v;
          throw new \InvalidArgumentException((is_scalar ($v) ? "The value <b>$v</b>" : 'A value') .
                                              " of PHP type <b>" .
                                              gettype ($v) .
                                              "</b> is not valid for a <b>data</b> attribute/parameter.");
      }
      if (isset(self::$TYPE_NAMES[$type]))
        throw new \InvalidArgumentException("Invalid attempt to validate an attribute/parameter value of type <b>" .
                                            self::$TYPE_NAMES[$type] . "<b> with code $type.");
    }
    return null;
  }

  public function __get ($name)
  {
    throw new ComponentException($this->component, "Can't read non existing attribute <b>$name</b>.");
  }

  public function __set ($name, $value)
  {
    throw new ComponentException($this->component, "Can't set non existing attribute <b>$name</b>.");
  }

  public function apply (array $attrs)
  {
    foreach ($attrs as $k => $v)
      $this->set ($k, $v);
  }

  /**
   * @param      $name
   * @param bool $asSubtag When true, the attribute MUST be able to be specified in subtag form.
   *                       When false, the attribute can be either a tag attribute or a subtag.
   * @return bool
   */
  public function defines ($name, $asSubtag = false)
  {
    if ($asSubtag) return $this->isSubtag ($name);
    return method_exists ($this, "typeof_$name");
  }

  public function get ($name, $default = null)
  {
    if (isset($this->$name))
      return $this->$name;
    return $default;
  }

  public function getAll ()
  {
    $p = $this->getAttributeNames ();
    $r = [];
    foreach ($p as $prop)
      $r[$prop] = $this->{$prop};
    return $r;
  }

  public function getAttributeNames ()
  {
    $a = array_keys (get_object_vars ($this));
    $a = array_diff ($a, ['component', '_modified']);
    return $a;
  }

  public function getAttributesOfType ($type)
  {
    $result = [];
    $names  = $this->getAttributeNames ();
    if (isset($names))
      foreach ($names as $name)
        if ($this->getTypeOf ($name) == $type)
          $result[$name] = $this->get ($name);
    return $result;
  }

  public function getEnumOf ($name)
  {
    return $this->{"enum_$name"}();
  }

  public function getScalar ($name)
  {
    return self::validateScalar ($this->getTypeOf ($name), $this->get ($name));
  }

  public function getTypeNameOf ($name)
  {
    $t = $this->getTypeOf ($name);
    if (!is_null ($t))
      return self::$TYPE_NAMES[$t];
    return self::$TYPE_NAMES[0];
  }

  public function getTypeOf ($name)
  {
    $fn = "typeof_$name";
    if (method_exists ($this, $fn))
      return $this->$fn();
    return Type::TEXT;
  }

  public function isEnum ($name)
  {
    return method_exists ($this, "enum_$name");
  }

  public function isScalar ($name)
  {
    $type = $this->getTypeOf ($name);
    return $type == Type::BOOL || $type == Type::ID || $type == Type::NUM ||
           $type == Type::TEXT;
  }

  public function isSubtag ($name)
  {
    $fn = "typeof_$name";
    if (method_exists ($this, $fn)) {
      switch ($this->$fn()) {
        case Type::SRC:
        case Type::PARAMS:
        case Type::METADATA:
          return true;
      }
    }
    return false;
  }

  public function set ($name, $value)
  {
    if (!$this->defines ($name))
      throw new ComponentException($this->component, "Invalid attribute <b>$name</b> specified.");
    if ($this->isScalar ($name))
      $this->setScalar ($name, $value);
    else switch ($type = $this->getTypeOf ($name)) {
      case Type::SRC:
        $ctx  = $this->component->context;
        $text = Text::from ($ctx, $value);
        if (isset($this->$name))
          $this->$name->addChild ($text);
        else {
          $param = new Parameter ($ctx, $name, $type);
          $param->attachTo ($this->component);
          $param->addChild ($text);
          $this->$name = $param;
        }
        $this->_modified = true;
        break;
      default:
        $this->$name     = $value;
        $this->_modified = true;
    }
  }

  public function setComponent (Component $owner)
  {
    $this->component = $owner;
    $attrs           = $this->getAttributesOfType (Type::SRC);
    foreach ($attrs as $name => $value)
      if (!is_null ($value)) {
        /** @var Component $c */
        $c = clone $value;
        $c->attachTo ($owner);
        $this->$name = $c;
      }
    $attrs = $this->getAttributesOfType (Type::PARAMS);
    foreach ($attrs as $name => $values)
      if (!empty($values))
        $this->$name = Component::cloneComponents ($values, $owner);
  }

  public function setScalar ($name, $v)
  {
    if ($this->isEnum ($name)) {
      $enum = $this->getEnumOf ($name);
      if (array_search ($v, $enum) === false) {
        $list = implode ('</b>, <b>', $enum);
        throw new ComponentException($this->component,
          "Invalid value for attribute/parameter <b>$name</b>.\nExpected: <b>$list</b>.");
      }
    }
    $newV = self::validateScalar ($this->getTypeOf ($name), $v);
    if ($this->$name !== $newV) {
      $this->$name = $newV;
      if (!isset(static::$NEVER_DIRTY[$name]))
        $this->_modified = true;
    }
  }

  protected function typeof__modified () { return Type::BOOL; }
}
