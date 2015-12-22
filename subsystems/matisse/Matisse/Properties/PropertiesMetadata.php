<?php
namespace Selenia\Matisse\Properties;

class PropertiesMetadata
{
  /**
   * Default values for each attribute.
   * <p>Map of property name => mixed
   * @var array
   */
  public $defaults = [];
  /**
   * Enumerations for each attributes.
   * <p>Map of property name => array
   * @var array[]
   */
  public $enums = [];
  /**
   * Mandatory attributes.
   * <p>Map of property name => true
   * @var array
   */
  public $required = [];
  /**
   * The types of each attribute.
   * <p>Map of property name => type::XXX
   * @var string[]
   */
  public $types = [];

}
