<?php
namespace Electro\Lib;

class Annotations
{
  private static $class = [];
  private static $meth  = [];
  private static $prop  = [];
  /**
   * @var array
   */
  private $annot;

  /**
   * @param string|mixed $class
   * @param string       $type One of: <pre>prop | meth | class</pre>
   * @param string       $id   Name of the desired identifier (property, method or class).
   * @return array
   */
  private static function &getMeta ($class, $type, $id)
  {
    if (!is_string ($class))
      $class = get_class ($class);
    if (!isset(self::${$type}[$class]))
      self::${$type}[$class] = [];
    $c =& self::${$type}[$class];
    if (!isset($c[$id]))
      $c[$id] = [];
    return $c[$id];
  }

  function get ($tag)
  {
    if (!isset($this->annot[$tag])) {
      $v = preg_match ('/' . $tag . '\s+([^@]+)/', $this->annot[$tag], $m) ? $m[1] : null;
      return $this->annot[$tag] = isset($v) ? trim (preg_replace ('/^\s*\*+\/?\s*/m', '', $v)) : null;
    }
    return $this->annot[$tag];
  }

  function has ($tag)
  {
    return isset($this->annot[$tag]) ? $this->annot[$tag] : null;
  }

  /**
   * # Reads annotations for the specified property.
   * @param string|mixed $class Fully qualified class name or an instance.
   * @param string       $name  The property name.
   * @return $this
   */
  function readProperty ($class, $name)
  {
    $meta =& self::getMeta ($class, 'prop', $name);
    if (isset($meta[$name])) {
      $this->annot =& $meta[$name];
      return $this;
    }
    $r                   = new \ReflectionMethod($class, $name);
    $this->annot['_doc'] = $r->getDocComment () ?: '';
    return $this;
  }

}
