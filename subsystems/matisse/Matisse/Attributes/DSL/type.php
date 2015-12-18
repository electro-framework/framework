<?php
namespace Selenia\Matisse\Attributes\DSL;

class type
{
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
   * Int or float.
   */
  const number = '§num';
  /**
   * This attribute type is a parameter with child components.
   */
  const parameter = '§param';
  /**
   * Parameter list. This attribute type is an array of Parameters with the same tag name.
   */
  const multipleParams = '§multi';
  /**
   * Plain text. Single-line or multi-line.
   */
  const text = '§text';
}
