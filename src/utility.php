<?php
// namespace \

//------------------------
//  Utility functions
//------------------------

/**
 * Reads a value from the given array at the specified index/key.
 * <br><br>
 * Unlike the usual array access operator [], this function does not generate warnings when
 * the key is not present on the array; instead, it returns null or a default value.
 *
 * @param array         $array The target array.
 * @param number|string $key   The list index or map key.
 * @param mixed         $def   An optional default value.
 *
 * @return mixed
 */
function get (array $array = null, $key, $def = null)
{
  if (!is_array ($array))
    return null;
  return isset ($array[$key]) ? $array[$key] : $def;
}

/**
 * Reads a value from the given object at the specified key.
 * <br><br>
 * Unlike the usual object access operator ->, this function does not generate warnings when
 * the key is not present on the object; instead, it returns null or the specified default value.
 *
 * @param object        $obj The target object.
 * @param number|string $key The property name.
 * @param mixed         $def An optional default value.
 *
 * @return mixed
 */
function property ($obj, $key, $def = null)
{
  return isset ($obj->$key) ? $obj->$key : $def;
}

function getField (&$data, $key, $default = null)
{
  if (is_object ($data))
    return isset($data->$key) ? $data->$key : $default;
  if (is_array ($data))
    return array_key_exists ($key, $data) ? $data[$key] : $default;
  return $default;
}

function map (array $a = null, callable $fn)
{
  return is_null ($a) ? null : array_map ($fn, $a);
}

function normalizeTagName ($name)
{
  return str_replace (' ', '', ucwords (str_replace ('-', ' ', strtolower ($name))));
}

function classNameToTagName ($name)
{
  return ltrim (strtolower (preg_replace ('/[A-Z]/', '_$0', $name)), '_');
}

function normalizeAttributeName ($name)
{
  return str_replace ('-', '_', strtolower ($name));
}

function denormalizeAttributeName ($name)
{
  return str_replace ('_', '-', $name);
}

function renameAttribute ($name)
{
  return str_replace ('-', '_', $name);
}

function strJoin ($s1, $s2, $delimiter)
{
  return strlen ($s1) && strlen ($s2) ? $s1 . $delimiter . $s2 : (strlen ($s1) ? $s1 : $s2);
}

function extractXML ($src)
{
  return preg_replace ('#^\s*<\?xml [^\?]*\?>\s*(?:<!DOCTYPE[\s\S]*?\]>)?#', '', $src);
}

function fileExists ($filename)
{
  $r = @fopen ($filename, 'rb', true);
  if ($r === false)
    return false;
  fclose ($r);
  return true;
}

function loadFile ($filename)
{
  $data = @file_get_contents ($filename, true);
  if ($data)
    return removeBOM ($data);
  return '';
}

function removeBOM ($string)
{
  if (substr ($string, 0, 3) == pack ('CCC', 0xef, 0xbb, 0xbf))
    $string = substr ($string, 3);
  return $string;
}

/**
 * Render a tree-like structure into HTML.
 *
 * @param array|string|null $e
 * @param int               $d Depth.
 * @return string
 * @throws \InvalidArgumentException
 */
function html ($e, $d = 0)
{
  //  echo "<pre>";print_r($e);exit;
  if (is_null ($e)) return '';
  if (is_string ($e)) return $e;
  if (isset($e['<'])) {
    $tag     = $e['<'];
    $attrs   = $e['@'];
    $content = $e['['];
    $s       = str_repeat (' ', $d);
    $o       = ($d ? "\n" : '') . "$s<$tag";
    foreach ($attrs as $k => $v) {
      $v = htmlspecialchars ($v);
      $o .= " $k=\"$v\"";
    }
    $o .= ">";
    if (empty($content))
      return "$o</$tag>";
    $o .= html ($content, $d + 1);
    return substr ($o, -1) == '>' ? "$o\n$s</$tag>" : "$o</$tag>";
  }
  if (is_Array ($e))
    return implode ('', map ($e, function ($v) use ($d) { return html ($v, $d + 1); }));
  throw new \InvalidArgumentException("Unsupported argument type for html(): " . get_type ($e));
}

/**
 * Creates an array representation of an html tag.
 *
 * @param string       $tagAndClasses Syntax: 'tag.class1.class2...classN', tag is optional.
 * @param array|string $attrs         Can also receive the value of $content.
 * @param array|string $content
 * @return array
 */
function h ($tagAndClasses, $attrs = [], $content = [])
{
  $t   = explode ('.', $tagAndClasses, 2);
  $tag = $t[0] ?: 'div';
  if (is_string ($attrs)) {
    $content = $attrs;
    $attrs   = [];
  } // Check if $content is specified instead of $attrs and adjust arguments.
  else if (array_key_exists (0, $attrs)) { // supports having a null as the first array item.
    $content = $attrs;
    $attrs   = [];
  }
  if (isset($t[1]))
    $attrs['class'] = (isset($attrs['class']) ? $attrs['class'] . ' ' : '') . str_replace ('.', ' ', $t[1]);
  return [
    '<' => $tag,
    '@' => $attrs,
    '[' => $content
  ];
}