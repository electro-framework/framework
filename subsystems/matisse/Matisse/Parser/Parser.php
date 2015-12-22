<?php
namespace Selenia\Matisse\Parser;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Components\Internal\Page;
use Selenia\Matisse\Components\Internal\Text;
use Selenia\Matisse\Components\Literal;
use Selenia\Matisse\Exceptions\ParseException;
use Selenia\Matisse\Interfaces\PropertiesInterface;
use Selenia\Matisse\Properties\Types\type;

class Parser
{
  const EXP_BEGIN            = '{{';
  const EXP_END              = '}}';
  const NO_TRIM              = 0;
  const PARSE_ATTRS          = '# ([\w\-\:]+) \s* (?: = \s* ("|\') (.*?) \2 )? (\s|@) #sxu';
  const PARSE_DATABINDINGS   = '# (?: \{\{ | \{!! ) ( .*? ) (?: \}\} | !!\} ) #xu';
  const PARSE_TAG            = '# (<) (/?) ([A-Z]\w+) \s* (.*?) (/?) (>) #sxu';
  const RAW_EXP_BEGIN        = '{!!';
  const RAW_EXP_END          = '!!}';
  const TRIM                 = 3;
  const TRIM_LEFT            = 1; // @ is at the end of the attrs string and it's used as a marker.
  const TRIM_LEFT_CONTENT    = '# (?<=\>) \s+ (?=\s) #xu';
  const TRIM_LITERAL_CONTENT = '# (?<=\>) \s+ (?=\s) | (?<=\s) \s+ (?=\<) #xu';
  const TRIM_RIGHT           = 2;
  const TRIM_RIGHT_CONTENT   = '# (?<=\s) \s+ (?=\<) #xu';
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
   * @var Component|Metadata
   */
  private $current;
  /**
   * The ending position of the tag currently being parsed.
   * @var int
   */
  private $currentTagEnd;
  /**
   * The starting position of the tag currently being parsed.
   * @var int
   */
  private $currentTagStart;
  /**
   * When set, all tags are created as parameters and added to the specified parameter's subtree.
   * @var Metadata
   */
  private $metadataContainer = null;
  /**
   * The ending position + 1 of the tag that was parsed previously.
   * @var int
   */
  private $prevTagEnd;
  /**
   * Indicates that a parameter of scalar type is being processed.
   *
   * @var boolean
   */
  private $scalarParam = false;
  /**
   * @var string The source markup being parsed.
   */
  private $source;

  function __construct (Context $context)
  {
    $this->context = $context;
  }

  /*********************************************************************************************************************
   * THE MAIN PARSING LOOP
   *******************************************************************************************************************
   * @param string    $body
   * @param Component $parent
   * @param Page      $root
   * @throws ParseException
   */
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
        $this->parse_text (trim (substr ($body, $pos, $start - $pos)));

      if ($term) {
        if ($attrs) $this->parsingError ('Closing tags must not have attributes.');
        $this->parse_closingTag ($tag);
      }
      else {
        // OPEN TAG

        if ($this->scalarParam)
          $this->parsingError ("Can't set tag <b>$tag</b> as a value for the scalar parameter <b>{$this->current->getTagName()}</b>.");

        if (isset($this->metadataContainer) || $this->subtag_check ($tag))
          $this->parse_subtag ($tag, $attrs);

        else $this->parse_componentTag ($tag, $attrs);

        // SELF-CLOSING TAG
        if ($term2)
          $this->parse_exitTagContext (true, $tag);
      }

      // LOOP: advance to the next component tag
      $pos = $end + 1;
    }

    // PROCESS REMAINING TEXT

    $nextContent = substr ($body, $pos);
    if (strlen ($nextContent))
      $this->parse_text (trim ($nextContent));
    $this->text_optimize ($parent);

    // DONE.
  }

  /*********************************************************************************************************************
   * PARSE ATTRIBUTES
   *********************************************************************************************************************
   * @param string $attrStr
   * @param array  $attributes
   * @param array  $bindings
   * @param bool   $processBindings
   */
  private function parse_attributes ($attrStr, array &$attributes = null, array &$bindings = null,
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
  private function parse_closingTag ($tag)
  {
    if ($tag != $this->current->getTagName ()) {
      $parent = $this->current->parent;
      $this->parsingError ("Closing tag mismatch.
<table>
  <tr><th>Found:<td class='fixed'><b>&lt;/$tag&gt;</b>
  <tr><th>Expected:<td class='fixed'><b>&lt;/{$this->current->getTagName ()}&gt;</b>
  <tr><th>Component in scope:<td class='fixed'><b>&lt;{$parent->getTagName()}></b><td>Class: <b>{$parent->className}</b>
" . (isset($parent->parent) ? "
  <tr><th>Scope's parent:<td><b>&lt;{$parent->parent->getTagName()}></b><td>Class: <b>{$parent->parent->className}</b>"
          : '') . "
</table>");
    }
    $this->parse_exitTagContext (true, $tag);
  }

  /*********************************************************************************************************************
   * PARSE A COMPONENT TAG
   *********************************************************************************************************************
   * @param string $tag
   * @param string $attrs
   * @throws ParseException
   */
  private function parse_componentTag ($tag, $attrs)
  {
    if (!$this->current->allowsChildren)
      $this->parsingError ("Neither the component <b>{$this->current->getTagName()}</b> supports children, neither the element <b>$tag</b> is a {$this->current->getTagName()} parameter.");

    /** @var Metadata|boolean $defParam */
    $this->parse_attributes ($attrs, $attributes, $bindings, true);
    $component =
      Component::create ($this->context, $this->current, $tag, $attributes, false /*TODO: support HTML components*/);

    $component->bindings = $bindings;
    $this->current->addChild ($component);
    $this->current = $component;
  }

  /*********************************************************************************************************************
   * EXIT THE CURRENT TAG CONTEXT (and go up)
   *********************************************************************************************************************
   * @param bool   $fromClosingTag
   * @param string $tag
   */
  private function parse_exitTagContext ($fromClosingTag = true, $tag = '')
  {
    $current = $this->current;
    $this->text_optimize ($current);
    if ($this->scalarParam)
      $this->scalarParam = false;

    $parent = $current->parent;
    $current->parsed (); //calling this method may unset the 'parent' property.

    // Check if the metadata context is being closed.
    if (isset($this->metadataContainer) && $this->current == $this->metadataContainer)
      unset ($this->metadataContainer);

    $this->current = $parent; //also discards the current scalar parameter, if that is the case.
  }

  /*********************************************************************************************************************
   * PARSE A SUBTAG
   *********************************************************************************************************************
   * @param string $tag
   * @param string $attrs
   * @throws ParseException
   */
  private function parse_subtag ($tag, $attrs)
  {
    $attrName = lcfirst ($tag);

    if (!$this->current instanceof Metadata) {

      // Create a component parameter

      if (!$this->current->supportsProperties)
        $this->parsingError ("The component <b>&lt;{$this->current->getTagName()}&gt;</b> does not support parameters.");
      $this->parse_attributes ($attrs, $attributes, $bindings);

      if (!$this->current->props ()->defines ($attrName)) {
        $s = '&lt;' . join ('>, &lt;', array_map ('ucfirst', $this->current->props ()->getPropertyNames ())) . '>';
        $this->parsingError ("The component <b>&lt;{$this->current->getTagName()}&gt;</b> ({$this->current->className})
does not support the specified parameter <b>$tag</b>.
<p>Expected: <span class='fixed'>$s</span>");
      }
      $param = $this->subtag_createParam ($attrName, $tag, $attributes, $bindings);
    }
    else {

      // Create parameter's subparameter

      $this->parse_attributes ($attrs, $attributes, $bindings);
      $param = $this->subtag_createSubParam ($attrName, $tag, $attributes, $bindings);
    }
  }

  /*********************************************************************************************************************
   * PARSE LITERAL TEXT
   *********************************************************************************************************************
   * @param string $text
   * @throws ParseException
   */
  private function parse_text ($text)
  {
    if (!empty($text)) {

      if ($this->current->allowsChildren) {
        $sPos = 0;
        //process data bindings
        while (preg_match (self::PARSE_DATABINDINGS, $text, $match, PREG_OFFSET_CAPTURE, $sPos)) {
          list(list($brData, $brPos)) = $match;
          if ($brPos > $sPos) //append previous literal content
            $this->text_addComponent (substr ($text, $sPos, $brPos - $sPos), self::TRIM_LEFT);
          //create databound literal
          $l = strlen ($brData);
          $this->text_addComponent ($brData);
          $sPos = $brPos + $l;
        }
        //append remaining literal content
        $this->text_addComponent (substr ($text, $sPos), self::TRIM_RIGHT);
      }

      else {
        $s = $this->current instanceof PropertiesInterface
          ? '&lt;' . join ('>, &lt;', array_map ('ucfirst', $this->current->props ()->getPropertyNames ())) . '>'
          : '';
        throw new ParseException("
<h4>You may not define literal content at this location.</h4>
<table>
  <tr><th>Component:<td class='fixed'>&lt;{$this->current->getTagName()}&gt;
  <tr><th>Expected&nbsp;tags:<td class='fixed'>$s
</table>", $this->source, $this->prevTagEnd, $this->currentTagStart);
      }

    }
  }

  private function parsingError ($msg)
  {
    throw new ParseException($msg, $this->source, $this->currentTagStart, $this->currentTagEnd);
  }

  /**
   * Checks if a tag is a subtag of the current component.
   * @param string $subtagName
   * @return bool
   */
  private function subtag_check ($subtagName)
  {
    $propName = lcfirst ($subtagName);
    // All descendants of a metadata parameter are always parameters.
    if ($this->current instanceof Metadata) {
      switch ($this->current->type) {
        case type::metadata:
          return true;
      }
      // Descendants of parameters not of metadata type cannot be parameters.
      return false;
    }
    // If the current component defines an attribute with the same name as the tag being checked, the tag is a parameter.
    return $this->current->supportsProperties && $this->current->props ()->defines ($propName, true);
  }

  private function subtag_createParam ($attrName, $tagName, array $attributes = null, array $bindings = null)
  {
    $component     = $this->current;
    $type          = $component->props ()->getTypeOf ($attrName);
    $this->current = $param = new Metadata($this->context, $tagName, $type, $attributes);
    $param->attachTo ($component);
    switch ($type) {
      case type::content:
        $component->props ()->$attrName = $param;
        $param->bindings                = $bindings;
        break;
      case type::metadata:
        $component->props ()->$attrName = $param;
        $param->bindings                = $bindings;
        $this->metadataContainer        = $param;
        break;
      case type::collection:
        if (isset($component->props ()->$attrName))
          $component->props ()->{$attrName}[] = $param;
        else $component->props ()->$attrName = [$param];
        $param->bindings = $bindings;
        break;
      default:
        $this->scalarParam = true;
    }
    return $param;
  }

  private function subtag_createSubParam ($name, $tagName, array $attributes = null, array $bindings = null)
  {
    $param              = $this->current;
    $this->current      = $subparam = new Metadata($this->context, $tagName, type::content, $attributes);
    $subparam->bindings = $bindings;
    $param->addChild ($subparam);
    return $subparam;
  }

  private function text_addComponent ($content, $trim = self::NO_TRIM)
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
        'value' => $content,
      ];
      if ($content[0] == '{') {
        $lit           = new Literal($this->context);
        $lit->bindings = $v;
      }
      else $lit = new Text ($this->context, $v);
      $this->current->addChild ($lit);
    }
  }

  /**
   * Merges adjacent Literal children of the specified container whenever that merge can be safely done.
   *
   * > Note: Although the parser doesn't generate redundant literals, they may occur after macro substitutions are
   * performed.
   * @param Component $c The container component.
   */
  private function text_optimize (Component $c)
  {
    $o    = [];
    $prev = null;
    if ($c->hasChildren ())
      foreach ($c->getChildren () as $child) {
        if ($prev && ($child instanceof Literal || $child instanceof Text) && empty($child->bindings)) {
          if (($prev instanceof Literal || $prev instanceof Text) &&
              empty($prev->bindings) && empty($child->bindings) &&
              !$prev->props ()->_modified && !$child->props ()->_modified
          ) {
            // safe to merge
            $prev->props ()->value .= $child->props ()->value;
            continue;
          }
        }
        $o[]  = $child;
        $prev = $child;
      }
    $c->setChildren ($o);
  }

}
