<?php
namespace Selenia\Matisse\Attributes\DSL;

use Selenia\Matisse\Components\Internal\Parameter;

class type
{
  /**
   * A map of attribute type identifiers to attribute type names.
   */
  const NAMES = [
    self::binding        => 'binding',
    self::bool           => 'bool',
    self::data           => 'data',
    self::id             => 'id',
    self::metadata       => 'metadata',
    self::multipleParams => 'multipleParams',
    self::number         => 'number',
    self::parameter      => 'parameter',
    self::text           => 'text',
  ];
  /**
   * Binding expression. This attribute is a string.
   * Do not define attributes/parameters of this type. It is used only on macro instances when binding expressions
   * are specified for macro parameters instead of constant values.
   */
  const binding = '§bind';
  /**
   * Boolean (1/0, yes/no, on/off, true/false).
   */
  const bool = '§bool';
  /**
   * Data source. This attribute type can be an array, an object or an iterable.
   */
  const data = '§data';
  /**
   * Alphanumeric identifier. Similar to the 'text' type, but with a narrower subset of allowable characters.
   */
  const id = '§id';
  /**
   * A parameter that only contains parameters. All children will be converted to parameters automatically.
   */
  const metadata = '§meta';
  /**
   * Parameter list. This attribute type is an array of Parameters with the same tag name.
   */
  const multipleParams = '§multi';
  /**
   * Int or float.
   */
  const number = '§num';
  /**
   * This attribute type is a parameter with child components.
   */
  const parameter = '§param';
  /**
   * Plain text. Single-line or multi-line.
   */
  const text = '§text';

  private static $BOOLEAN_VALUES = [
    0       => false,
    1       => true,
    'false' => false,
    'true'  => true,
    'no'    => false,
    'yes'   => true,
    'off'   => false,
    'on'    => true,
  ];

  /**
   * Converts a type name to a type identifier (one of the `type::XXX` constants).
   * @param string $name
   * @return string|false
   */
  static function getIdOf ($name)
  {
    return array_search ($name, self::NAMES);
  }

  /**
   * Converts a type identifier (one of the `type::XXX` constants) to a type name.
   * @param string $id
   * @return string|false
   */
  static function getNameOf ($id)
  {
    return get (self::NAMES, $id, false);
  }

  /**
   * Converts a boolean textual representation into a true boolean value.
   * @param string $v
   * @return bool
   */
  static function toBoolean ($v)
  {
    if (is_bool ($v))
      return $v;
    if (is_string ($v) && isset (self::$BOOLEAN_VALUES[$v]))
      return self::$BOOLEAN_VALUES[$v];
    return !is_null ($v) && !empty($v);
  }

  /**
   * Validates a value against a specific type.
   * @param string $type
   * @param mixed $v
   * @return bool
   */
  static function validate ($type, $v)
  {
    if (is_null ($v) || $v === '')
      return true;

    switch ($type) {

      case type::binding:
        return is_string ($v);

      case type::bool:
        return is_bool ($v) || isset (self::$BOOLEAN_VALUES[$v]);

      case type::data:
        return is_array ($v) || is_object ($v) || $v instanceof \Traversable;

      case type::id:
        return !!preg_match ('#^\w+$#', $v);

      case type::parameter:
      case type::metadata:
        return $v instanceof Parameter;

      case type::multipleParams:
        return is_array ($v);

      case type::number:
        return is_numeric ($v);

      case type::text:
        return is_scalar ($v);
    }
    return false;
  }

}
