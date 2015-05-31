<?php
namespace Selene\Matisse;
use Selene\Matisse\Components\Literal;
use Selene\Matisse\Components\Page;
use Selene\Matisse\Components\Parameter;
use Selene\Matisse\Exceptions\ParseException;

class Parser
{
  const PARSE_TAG            = '# (<) (/?) ([A-Z]\w+) \s* (.*?) (/?) (>) #sx';
  const PARSE_DATABINDINGS   = '# \{(?=\S) ( [^{}]* | \{[^{}]*\} )* \} #x';
  const TRIM_LITERAL_CONTENT = '# ^ \s+ | (?<=\>) \s+ (?=\s) | (?<=\s) \s+ (?=\<) | \s+ $ #x';
  const TRIM_LEFT_CONTENT    = '# ^ \s+ | (?<=\>) \s+ (?=\s) #x';
  const TRIM_RIGHT_CONTENT   = '# (?<=\s) \s+ (?=\<) | \s+ $ #x';
  const PARSE_ATTRS          = '# ([\w\-\:]+) \s* (?: = \s* ("|\') (.*?) \2 )? (\s|@) #sx'; // @ is at the end of the attrs string and it's used as a marker.
  const NO_TRIM              = 0;
  const TRIM_LEFT            = 1;
  const TRIM_RIGHT           = 2;
  const TRIM                 = 3;

  /**
   * Points to the root of the component hierarchy.
   *
   * @var Page
   */
  public $root;
  /**
   * The rendering context for the current request.
   * @var Context
   */
  private $context;
  /**
   * Points to the component being currently processed on the components tree.
   *
   * @var Component|Parameter
   */
  private $current;
  /**
   * Indicates that a parameter of scalar type is being processed.
   *
   * @var boolean
   */
  private $scalarParam = false;
  /**
   * When set, all tags are created as parameters and added to the specified parameter's subtree.
   * @var Parameter
   */
  private $metadataContainer = null;
  /**
   * The ending position + 1 of the tag that was parsed previously.
   * @var int
   */
  private $prevTagEnd;
  /**
   * The starting position of the tag currently being parsed.
   * @var int
   */
  private $currentTagStart;
  /**
   * The ending position of the tag currently being parsed.
   * @var int
   */
  private $currentTagEnd;
  /**
   * @var string The source markup being parsed.
   */
  private $source;

  function __construct (Context $context)
  {
    $this->context = $context;
  }

  /*********************************************************************************************************************
   * THE PARSING LOOP
   ********************************************************************************************************************/
  public function parse ($body, Component $parent, Page $root = null)
  {
    $pos = 0;
    if (!$root) $root = $parent;
    $this->current = $parent;
    $this->root    = $root;
    $this->source  = $body;

    while (preg_match (self::PARSE_TAG, $body, $match, PREG_OFFSET_CAPTURE, $pos)) {
      list(, list(, $start), list($term), list($tag), list($attrs), list($term2), list(, $end)
        ) = $match;

      $this->prevTagEnd      = $pos;
      $this->currentTagStart = $start;
      $this->currentTagEnd   = $end;

      if ($start > $pos)
        $this->parseLiteral (substr ($body, $pos, $start - $pos));

      if ($term) {
        if ($attrs) $this->error ('Closing tags must not have attributes.');
        $this->parseClosingTag ($tag);
      }
      else {
        // OPEN TAG

        if ($this->scalarParam)
          $this->error ("Can't set tag <b>$tag</b> as a value for the scalar parameter <b>{$this->current->getTagName()}</b>.");

        _log ("OPEN $tag ON {$this->current->getTagName()}, meta context?", isset($this->metadataContainer),
          "implicit?", isset($this->current->isImplicit) && $this->current->isImplicit);

        if (isset($this->metadataContainer) || $this->isParameter ($tag))
          $this->parseParameter ($tag, $attrs);

        else $this->parseComponent ($tag, $attrs);

        // SELF-CLOSING TAG
        if ($term2)
          $this->tagComplete (true, $tag);
      }

      // LOOP: advance to the next component tag
      $pos = $end + 1;
    }

    // PROCESS REMAINING TEXT

    $nextContent = substr ($body, $pos);
    if (strlen ($nextContent)) $this->processLiteral (trim ($nextContent));

    // DONE.
  }

  /*********************************************************************************************************************
   * PARSE A COMPONENT TAG
   *********************************************************************************************************************
   * @param string $tag
   * @param string $attrs
   * @throws ParseException
   */
  private function parseComponent ($tag, $attrs)
  {
    if (!$this->current->allowsChildren)
      $this->error ("The component <b>&lt;{$this->current->getTagName()}&gt;</b> does not support parameters.");

    /** @var Parameter|boolean $defParam */
    $this->parseAttributes ($attrs, $attributes, $bindings, true);
    _log ("Create COMPONENT $tag");
    $component = Component::create ($this->context, $this->current, $tag, $attributes,
      false /*TODO: support HTML components*/);

    $component->bindings = $bindings;
    $this->current->addChild ($component);
    $this->current = $component;
  }

  /*********************************************************************************************************************
   * PARSE A SUBTAG
   *********************************************************************************************************************
   * @param string $tag
   * @param string $attrs
   * @throws ParseException
   */
  private function parseParameter ($tag, $attrs)
  {
    // Allow the placement of additional parameters after the content of a default (implicit) parameter.
    if (isset($this->current->isImplicit) && $this->current->isImplicit) {
      _log ("CLOSE IMPLICIT PARAM WHEN OPENING NEW PARAM");
      $this->tagComplete (false);
    }
    $attrName = lcfirst ($tag);

    if (!$this->current instanceof Parameter) {
      // Create a component parameter

      if (!$this->current->supportsAttributes)
        $this->error ("The component <b>&lt;{$this->current->getTagName()}&gt;</b> does not support parameters.");
      $this->parseAttributes ($attrs, $attributes, $bindings);

      if (!$this->current->attrs ()->defines ($attrName)) {
        $s = '&lt;' . join ('>, &lt;', array_map('ucfirst', $this->current->attrs ()->getAttributeNames ())) . '>';
        $this->error ("The component <b>&lt;{$this->current->getTagName()}&gt;</b> ({$this->current->className})
does not support the specified parameter <b>$tag</b>.
<p>Expected: <span class='fixed'>$s</span>");
      }
      $param = $this->createParameter ($attrName, $tag, $attributes, $bindings);
    }
    else {

      // Create parameter's subparameter

      $this->parseAttributes ($attrs, $attributes, $bindings);
      $param = $this->createSubparameter ($attrName, $tag, $attributes, $bindings);
    }
  }

  /*********************************************************************************************************************
   * PARSE ATTRIBUTES
   *********************************************************************************************************************
   * @param string $attrStr
   * @param array  $attributes
   * @param array  $bindings
   * @param bool   $processBindings
   */
  private function parseAttributes ($attrStr, array &$attributes = null, array &$bindings = null,
                                    $processBindings = true)
  {
    $attributes = $bindings = null;
    if (!empty($attrStr)) {
      $sPos = 0;
      while (preg_match (self::PARSE_ATTRS, "$attrStr@", $match, PREG_OFFSET_CAPTURE, $sPos)) {
        list(, list($key), list($quote), list($value, $exists), list($marker, $next)) = $match;
        if ($exists < 0)
          $value = 'true';
        if ($processBindings && strpos ($value, '{') !== false)
          $bindings[renameAttribute ($key)] = $value;
        else $attributes[renameAttribute ($key)] = $value;
        $sPos = $next;
      }
    }
  }

  /*********************************************************************************************************************
   * PARSE CLOSING TAG
   *********************************************************************************************************************
   * @param string $tag
   * @throws ParseException
   */
  private function parseClosingTag ($tag)
  {
    if ($tag != $this->current->getTagName ()) {
      $parent = $this->current->parent;

      // If the current context is an implicit parameter and we are closing the tag of the parameter's owner,
      // proceed, otherwise the closing tag is mismatched.

      if ($this->current instanceof Parameter && $this->current->isImplicit &&
          $tag == $this->current->parent->getTagName ()
      ) {
        // Closing a component's tag must also close an implicit parameter.
        _log ("CLOSE IMPLICIT PARAM WHEN CLOSING COMPONENT TAG </$tag>");
        $this->tagComplete (false, 'implicit');
      }
      else $this->error ("Closing tag mismatch.
<table>
  <tr><th>Found:<td class='fixed'><b>&lt;/$tag&gt;</b>
  <tr><th>Expected:<td class='fixed'><b>&lt;/{$this->current->getTagName ()}&gt;</b>
  <tr><th>Component in scope:<td class='fixed'><b>&lt;{$parent->getTagName()}></b><td>Class: <b>{$parent->className}</b>
  <tr><th>Scope's parent:<td><b>&lt;{$parent->parent->getTagName()}></b><td>Class: <b>{$parent->parent->className}</b>
</table>");
    }
    $this->tagComplete (true, $tag);
  }

  /*********************************************************************************************************************
   * END THE CURRENT TAG CONTEXT
   *********************************************************************************************************************
   * @param bool   $fromClosingTag
   * @param string $tag
   */
  private function tagComplete ($fromClosingTag = true, $tag = '')
  {
    $current = $this->current;
    _log ("CLOSE </$tag> from closing tag?", $fromClosingTag, "implicit?",
      isset($current->parent->isImplicit) && $current->parent->isImplicit);
    $this->mergeLiterals ($current);
    if ($this->scalarParam)
      $this->scalarParam = false;

    $parent = $current->parent;
    $current->parsed (); //calling this method may unset the 'parent' property.

    // Check if the metadata context is being closed.
    if (isset($this->metadataContainer) && $this->current == $this->metadataContainer) {
      _log ("END META", $this->current->getTagName (), "parent", $parent->getTagName ());
      unset ($this->metadataContainer);
    }

    $this->current = $parent; //also discards the current scalar parameter, if that is the case.
  }

  /*********************************************************************************************************************
   * PARSE LITERAL TEXT
   *********************************************************************************************************************
   * @param string $text
   * @throws ParseException
   */
  private function parseLiteral ($text)
  {
    $text = trim ($text);
    if (!empty($text)) {
      _log ("Create LITERAL", $text);
      if ($this->scalarParam) $this->current->setScalar ($text);
      else {
        if (!$this->current->allowsChildren) {
          $s = $this->current instanceof IAttributes ?
            '&lt;' . join ('>, &lt;', array_map('ucfirst', $this->current->attrs ()->getAttributeNames ())) . '>' : '';
          throw new ParseException("
<h4>You may not define literal content at this location.</h4>
<table>
  <tr><th>Component:<td class='fixed'>&lt;{$this->current->getTagName()}&gt;
  <tr><th>Expected&nbsp;tags:<td class='fixed'>$s
</table>", $this->source, $this->prevTagEnd, $this->currentTagStart);
        }
      }
      $this->processLiteral ($text);
    }
  }

  /**
   * Checkes if a subtag is a parameter of the current component.
   * @param string $subtagName
   * @return bool
   */
  private function isParameter ($subtagName)
  {
    $attrName = lcfirst ($subtagName);
    // All descendants of a metadata parameter are always parameters.
    if ($this->current instanceof Parameter) {
      switch ($this->current->type) {
        case AttributeType::METADATA:
          return true;
      }
      // Descendants of parameters not of metadata type cannot be parameters.
      return false;
    }
    // If the current component defines an attribute with the same name as the tag being checked, the tag is a parameter.
    return $this->current instanceof IAttributes && $this->current->attrs ()->defines ($attrName);
  }

  /**
   * Merges adjacent Literal children of the specified container whenever that merge can be safely done.
   *
   * > Note: Although the parser doesn't generate redundant literals, they may occur after macro substitutions are
   * performed.
   * @param Component $c The container component.
   */
  private function mergeLiterals (Component $c)
  {
    $o    = [];
    $prev = null;
    if (isset($c->children))
      foreach ($c->children as $child) {
        if ($prev && $prev instanceof Literal && $child instanceof Literal && !$prev->bindings && !$child->bindings
            && !$prev->attrs ()->_modified && !$child->attrs ()->_modified
        ) {
          // safe to merge
          $prev->attrs ()->value .= $child->attrs ()->value;
        }
        else {
          $o[]  = $child;
          $prev = $child;
        }
      }
    $c->children = $o;
  }

  private function createParameter ($attrName, $tagName, array $attributes = null, array $bindings = null)
  {
    _log ("CREATE PARAMETER $tagName");
    $component     = $this->current;
    $type          = $component->attrs ()->getTypeOf ($attrName);
    $this->current = $param = new Parameter($this->context, $tagName, $type, $attributes);
    $param->attachTo ($component);
    switch ($type) {
      case AttributeType::SRC:
        $component->attrs ()->$attrName = $param;
        $param->bindings                = $bindings;
        break;
      case AttributeType::METADATA:
        $component->attrs ()->$attrName = $param;
        $this->metadataContainer        = $param;
        break;
      case AttributeType::PARAMS:
        if (isset($component->attrs ()->$attrName))
          $component->attrs ()->{$attrName}[] = $param;
        else $component->attrs ()->$attrName = [$param];
        $param->bindings = $bindings;
        break;
      default:
        $this->scalarParam = true;
    }
    return $param;
  }

  private function createSubparameter ($name, $tagName, array $attributes = null, array $bindings = null)
  {
    _log ("CREATE SUBPARAMETER $name");
    $param              = $this->current;
    $this->current      = $subparam = new Parameter($this->context, $tagName, AttributeType::SRC, $attributes);
    $subparam->bindings = $bindings;
    $param->addChild ($subparam);
    return $subparam;
  }

  private function error ($msg)
  {
    throw new ParseException($msg, $this->source, $this->currentTagStart, $this->currentTagEnd);
  }

  private function processLiteral ($content)
  {
    $sPos = 0;
    //process data bindings
    while (preg_match (self::PARSE_DATABINDINGS, $content, $match, PREG_OFFSET_CAPTURE, $sPos)) {
      list(list($brData, $brPos)) = $match;
      if ($brPos > $sPos) //append previous literal content
        $this->addLiteral (substr ($content, $sPos, $brPos - $sPos), self::TRIM_LEFT);
      //create databound literal
      $l = strlen ($brData);
      $this->addLiteral ($brData);
      $sPos = $brPos + $l;
    }
    //append remaining literal content
    $this->addLiteral (substr ($content, $sPos), self::TRIM_RIGHT);
  }

  private function addLiteral ($content, $trim = self::NO_TRIM)
  {
    if ($this->context->condenseLiterals)
      switch ($trim) {
        case self::TRIM_LEFT:
          $content = preg_replace (self::TRIM_LEFT_CONTENT, '', $content);
          break;
        case self::TRIM_RIGHT:
          $content = preg_replace (self::TRIM_RIGHT_CONTENT, '', $content);
          break;
        case self::TRIM:
          $content = preg_replace (self::TRIM_LITERAL_CONTENT, '', $content);
          break;
      }
    if (strlen ($content)) {
      $v = [
        'value' => $content
      ];
      if ($content[0] == '{') {
        $lit           = new Literal($this->context);
        $lit->bindings = $v;
      }
      else $lit = new Literal($this->context, $v);
      $this->current->addChild ($lit);
    }
  }
}
