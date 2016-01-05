<?php

//------------------------------
//  Matisse-specific functions
//------------------------------

/**
 * Converts a dash-separated tag name into a camel case tag name.
 * > Ex: `<my-tag>` -> `<myTag>`
 *
 * @param string $name
 * @return string
 */
function normalizeTagName ($name)
{
  return str_replace (' ', '', ucwords (str_replace ('-', ' ', $name)));
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

/**
 * Unified interface for retrieving a value by property/method name from an object or by key from an array.
 *
 * ### On an object
 * - If a property is inaccessible, it calls `getProperty()` if it exists, otherwise it returns the default value.
 * - If a property does not exist, it tries to call `property()` if it exists, otherwise, it returns the default value.
 *
 * ### On an array
 * - Returns the item at the specified key, or the default value if the key doesn't exist.
 *
 * @param array|object $data
 * @param string       $key
 * @param mixed        $default Value to return if the key doesn't exist or it's not accessible trough know methods.
 * @return mixed
 */
function _g ($data, $key, $default = null)
{
  if (is_object ($data)) {
    if (property_exists ($data, $key)) {
      if (isset($data->$key))
        return $data->$key;
      // Property may be private/protected, try to call a getter method with the same name
      if (method_exists ($data, $key) || method_exists ($data, '__call'))
        return $data->$key ();
      return $default;
    }
    if ($data instanceof \ArrayAccess && isset ($data[$key]))
      return $data[$key];
    if (is_callable ([$data, $key]))
      return $data->$key ();
    return $default;
  }
  if (is_array ($data))
    return array_key_exists ($key, $data) ? $data[$key] : $default;
  return $default;
}
