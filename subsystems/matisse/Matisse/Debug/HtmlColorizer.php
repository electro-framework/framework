<?php
namespace Selenia\Matisse\Debug;

class HtmlColorizer
{
  const EXPECT_ATTR                = 'ATT';
  const EXPECT_OPEN_TAG            = 'OPE';
  const EXPECT_TAG_NAME_OR_COMMENT = 'TAG';
  const EXPECT_TEXT                = 'TXT';
  const EXPECT_VALUE_OR_ATTR       = 'VAT';

  const IS_STARTING_INSIDE_TAG = <<<'REGEX'
%
  ^ [^<]* >         # anything not an < up to the first >
%xu
REGEX;

  const MATCH = [

    self::EXPECT_TEXT => <<<'REGEX'
%
  (?P<t> [^<]*)     # anything up to the next < or the string end
%xu
REGEX
    ,

    self::EXPECT_OPEN_TAG => <<<'REGEX'
%
  (?P<t> < )        # must be an <
%xu
REGEX
    ,

    self::EXPECT_TAG_NAME_OR_COMMENT => <<<'REGEX'
%
  (?P<t>
    /?              # optional /
    (?:             # either
      !-- .*? -->   # HTML comment
      |             # or
      [\w\-\:]+     # tag name, with optional prefix
    )
  )
%xus
REGEX
    ,

    self::EXPECT_ATTR => <<<'REGEX'
%
  (?P<s> \s*)
  (?P<t>
    (?:             # either
      /? >          # end of tag: /> or >
      |             # or
      [^\s=/>]+     # attribute name (anything up to a space, =, /> or >)
    )
  )
%xu
REGEX
    ,

    self::EXPECT_VALUE_OR_ATTR => <<<'REGEX'
%
  (?P<s> \s*)
  (?P<t>
    (?:                           # either
      /? >                        # end of tag: /> or >
      |                           # or
      = \s* "[^"]* (?: " | $)     # ="quoted" or ="unclosed
      |                           # or
      = \s* '[^']* (?: ' | $)     # ='quoted' or ='unclosed
      |                           # or
      = \s* (?! " | ') [^\s>]+    # =unquoted (up to the first space or >)
      |                           # or
      [^\s=/>]+                   # next attribute name (current attribute has no value)
    )
  )
%xu
REGEX
    ,
  ];

  static function colorize ($s)
  {
    $o     = '';
    $state = self::EXPECT_TEXT;
    // Check if the input starts midway a tag
    if (preg_match (self::IS_STARTING_INSIDE_TAG, $s, $m)) {
      $state = self::EXPECT_ATTR;
    }

    while (preg_match (self::MATCH[$state], $s, $m)) {

      if (isset($m['s']))
        $o .= $m['s'];

      $v = $m['t'];
      $e = htmlentities ($v, ENT_QUOTES, 'UTF-8', false);
      switch ($state) {

        case self::EXPECT_ATTR:
          if ($v[0] == '/' || $v == '>') {
            $o .= $e;
            $state = self::EXPECT_TEXT;
          }
          else {
            $o .= "<span style=\"color:#AC7B53\">$e</span>";
            $state = self::EXPECT_VALUE_OR_ATTR;
          }
          break;

        case self::EXPECT_OPEN_TAG:
          $o .= $v;
          $state = self::EXPECT_TAG_NAME_OR_COMMENT;
          break;

        case self::EXPECT_TAG_NAME_OR_COMMENT:
          if ($v[0] == '!') {
            $o .= "<span style=\"color:#2A802A\">$e</span>";
            $state = self::EXPECT_TEXT;
          }
          else {
            $o .= "<span style=\"color:#991590\">$e</span>";
            $state = self::EXPECT_ATTR;
          }
          break;

        case self::EXPECT_TEXT:
          $o .= $e;
          $state = self::EXPECT_OPEN_TAG;
          break;

        case self::EXPECT_VALUE_OR_ATTR:
          if ($v[0] == '=') {
            $o .= '=<span style="color:#1A1A99">' . substr ($e, 1) . '</span>';
            $state = self::EXPECT_ATTR;
          }
          elseif ($v[0] == '/' || $v == '>') {
            $o .= $e;
            $state = self::EXPECT_TEXT;
          }
          else {
            $o .= "<span style=\"color:#AC7B53\">$e</span>";
            $state = self::EXPECT_VALUE_OR_ATTR;
          }
          break;
      }
      $s = substr ($s, strlen ($m[0]));
    }
    return $o;
  }

}
