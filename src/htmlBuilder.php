<?php

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
