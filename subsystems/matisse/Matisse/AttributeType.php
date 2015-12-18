<?php
namespace Selenia\Matisse;

class AttributeType
{
  /** Alphanumeric identifier. */
  const ID = 1;
  /** Plain text. */
  const TEXT = 2;
  /** Number */
  const NUM = 3;
  /** Boolean (1/0, yes/no, on/off, true/false). */
  const BOOL = 4;
  /** Parameter list. This attribute type is an array of Parameters. */
  const PARAMS = 5;
  /** Source code. This attribute type is a parameter with child components. */
  const SRC = 6;
  /** Data source. This attribute type is a DataSource object. */
  const DATA = 7;
  /**
   * Binding expression. This attribute is a string.
   * Do not define attributes/parameters of this type. It is used only on macro instances when binding expreesions
   * are specified for macro parameters instead of constant values.
   */
  const BINDING = 8;
  /** A parameter that can contain other parameters. */
  const METADATA = 9;
}
