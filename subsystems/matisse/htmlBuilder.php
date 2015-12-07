<?php

/**
 * An array containing the names of the HTML tags which must not have a closing tag.
 * @var array
 */
$VOID_ELEMENTS = [
  'area'    => 1,
  'base'    => 1,
  'br'      => 1,
  'col'     => 1,
  'command' => 1,
  'embed'   => 1,
  'hr'      => 1,
  'img'     => 1,
  'input'   => 1,
  'keygen'  => 1,
  'link'    => 1,
  'meta'    => 1,
  'param'   => 1,
  'source'  => 1,
  'track'   => 1,
  'wbr'     => 1,
];

/**
 * Creates an array representation of an html tag.
 *
 * @param string       $tagAndClasses Syntax: 'tag.class1.class2...classN', tag is optional.
 * @param array|string $attrs         Can also receive the value of $content, but BEWARE: the content array MUST have
 *                                    integer keys, otherwise it will be interpreted as an attributes array.
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
    '[' => $content,
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
  global $VOID_ELEMENTS;
  if (is_null ($e)) return '';
  if (is_string ($e)) return $e;
  if (isset($e['<'])) {
    $tag     = $e['<'];
    $attrs   = $e['@'];
    $content = $e['['];
    $s       = str_repeat (' ', $d);
    $o       = ($d ? "\n" : '') . "$s<$tag";
    foreach ($attrs as $k => $v) {
      if (is_array ($v)) {
        0 / 0;
      }
      $v = htmlspecialchars ($v);
      $o .= " $k=\"$v\"";
    }
    $o .= ">";
    $c = isset($VOID_ELEMENTS[$tag]) ? '' : "</$tag>";
    if (empty($content))
      return "$o$c";
    $o .= html ($content, $d + 1);
    return substr ($o, -1) == '>' ? "$o\n$s$c" : "$o$c";
  }
  if (is_array ($e))
    return implode ('', map ($e, function ($v) use ($d) { return html ($v, $d + 1); }));
  throw new \InvalidArgumentException("Unsupported argument type for html(): " . gettype ($e));
}
