<?php
namespace selene\matisse;

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

function fileExists ($filename)
{
  $r = @fopen ($filename, 'rb', true);
  if ($r === false)
    return false;
  fclose ($r);
  return true;
}

function loadFile ($filename, $useIncludePath = true)
{
  $data = @file_get_contents ($filename, $useIncludePath);
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
