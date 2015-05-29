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

  const EXPECT_COMPONENT_OR_TEXT      = 1;
  const EXPECT_PARAM_OR_DEFAULT_PARAM = 2;
  const EXPECT_METADATA               = 3;

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
   * When set, all tags are created as paramenters and added to the specified parameter's subtree.
   * @var Parameter
   */
  private $metadataContainer = null;

  function __construct (Context $context)
  {
    $this->context = $context;
  }

  public function parse ($body, Component $parent, Page $root = null)
  {
    $pos = 0;
    if (!$root) $root = $parent;
    $this->current = $parent;
    $this->root    = $root;
    while (preg_match (self::PARSE_TAG, $body, $match, PREG_OFFSET_CAPTURE, $pos)) {
      list(, list(, $start), list($term), list($tag), list($attrs), list($term2), list(, $end)
        ) = $match;
      //literal content
      if ($start > $pos) {
        $v = trim (substr ($body, $pos, $start - $pos));
        if (!empty($v)) {
_log("Create LITERAL", $v);
          if ($this->scalarParam) $this->current->setScalar ($v);
          else {
            if (!$this->canAddComponent ()) {
              if (!isset($this->current->defaultAttribute)) {
                $s = join (', ', $this->current->attrs ()->getAttributeNames ());
                throw new ParseException("<h4>You may not define literal content at this.location.</h4>
<table>
  <tr><th>Component:<td>&lt;{$this->current->getTagName()}&gt;
  <tr><th>Expected parameters:<td>$s
</table>",
                  $body, $pos, $start);
              }
              if (!$this->current instanceof Parameter)
                $this->generateImplicitParameter ();
            }
            $this->processLiteral ($v);
          }
        }
      }

      // PROCESS TAG

      if ($term) {

        // CLOSE PREVIOUS TAG

        if ($tag != $this->current->getTagName ()) {
          $parent = $this->current->parent;

          // If the current context is an implicit parameter and we are closing the tag of the parameter's owner,
          // proceed, otherwise the closing tag is mismatched.

          if ($this->current instanceof Parameter && $this->current->isImplicit && $tag == $this->current->parent->getTagName ()) {
            // Closing a component's tag must also close an implicit parameter.
_log ("CLOSE IMPLICIT PARAM WHEN CLOSING COMPONENT TAG </$tag>");
              $this->tagComplete (false, 'implicit');
          }
          else throw new ParseException("Closing tag mismatch.
<table>
  <tr><th>Found:<td><b>&lt;/$tag&gt;</b>
  <tr><th>Expected:<td><b>&lt;/{$this->current->getTagName ()}&gt;</b>
  <tr><th>Component in scope:<td><b>&lt;{$parent->getTagName()}></b><td>Class: <b>{$parent->className}</b>
  <tr><th>Scope's parent:<td><b>&lt;{$parent->parent->getTagName()}></b><td>Class: <b>{$parent->parent->className}</b>
</table>",
              $body, $start, $end);
        }
        if ($attrs) throw new ParseException('Closing tags must not have attributes.', $body, $start, $end);
        $this->tagComplete (true, $tag);
      }
      else {

        // OPEN TAG

        if ($this->scalarParam)
          throw new ParseException("Can't set tag <b>$tag</b> as a value for the scalar parameter <b>{$this->current->getTagName()}</b>.",
            $body, $start, $end);
        $attributes = $bindings = null;

        $attrName = lcfirst ($tag);
_log ("OPEN $attrName ON {$this->current->getTagName()}, meta context?", isset($this->metadataContainer),"implicit?",isset($this->current->isImplicit) && $this->current->isImplicit);
        if (isset($this->metadataContainer) || $this->isParameter ($attrName)) {

          // IT'S A PARAMETER

          if (!$this->canAddParameter ()) {
            throw new ParseException("Parameters must be specified as direct descendant nodes of a component.
            <table>
              <th>Parameter:<td>$tag
              <tr><th>Container:<td>{$this->current->getTagName()} ({$this->current->className})
              <tr><th>
            </table>",
              $body, $start, $end);
          }
          // Allow the placement of additional parameters after the content of a default (implicit) parameter.
          if (isset($this->current->isImplicit) && $this->current->isImplicit) {
_log ("CLOSE IMPLICIT PARAM WHEN OPENING NEW PARAM");
            $this->tagComplete (false);
          }
          if (!$this->current instanceof Parameter) {
            // Create a component parameter

            if (!$this->current->supportsAttributes)
              throw new ParseException("The component <b>&lt;{$this->current->getTagName()}&gt;</b> does not support parameters.",
                $body, $start,
                $end);
            $this->parseAttributes ($attrs, $attributes, $bindings);
            if (!$this->current->attrs ()->defines ($attrName)) {
              $s = join ('</b>, <b>', $this->current->attrs ()->getAttributeNames ());
              throw new ParseException("The component <b>&lt;{$this->current->getTagName()}&gt;</b> ({$this->current->className}) does not support the specified parameter <b>$tag</b>.<p>Expected: <b>$s</b>.",
                $body, $start, $end);
            }
            $param = $this->createParameter ($attrName, $tag, $attributes, $bindings);
          }
          else {

            // Create parameter's subparameter

            $this->parseAttributes ($attrs, $attributes, $bindings);
            $param = $this->createSubparameter ($attrName, $tag, $attributes, $bindings);
          }
        }
        else {

          // IT'S A COMPONENT OR A MACRO

          if (!$this->canAddComponent ()) {
            if (!isset($this->current->defaultAttribute))
              throw new ParseException('You may not instantiate a component at this location.', $body,
                $start, $end);
            $this->generateImplicitParameter ();
          }
          /** @var Parameter|boolean $defParam */
          $this->parseAttributes ($attrs, $attributes, $bindings, true);
_log("Create COMPONENT $tag");
          $component = Component::create ($this->context, $this->current, $tag, $attributes,
            false /*TODO: support HTML components*/);

          $component->bindings = $bindings;
          $this->current->addChild ($component);
          $this->current = $component;
        }
        //short tag
        if ($term2)
          $this->tagComplete (true, $tag);
      }
      $pos = $end + 1;
    }
    $nextContent = substr ($body, $pos);
    if (strlen ($nextContent)) $this->processLiteral (trim ($nextContent));
  }

  protected function isParameter ($attrName)
  {
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

  protected function generateImplicitParameter ()
  {
_log("Create IMPLICIT PARAMETER");
    $current = $this->current;
    $defName = $current->defaultAttribute;
    // Synthesise a parameter only when no corresponding one already exists.
    if (is_null ($current->attrs ()->$defName))
      $this->createParameter ($defName, ucfirst ($defName))->isImplicit = true;

  }

  protected function tagComplete ($fromClosingTag = true, $tag = '')
  {
    $current = $this->current;
_log ("CLOSE </$tag> from closing tag?", $fromClosingTag,"implicit?",isset($current->parent->isImplicit) && $current->parent->isImplicit);
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

  /**
   * Merges adjacent Literal children of the specified container whenever that merge can be safely done.
   *
   * > Note: Although the parser doesn't generate redundant literals, they may occur after macro substitutions are
   * performed.
   * @param Component $c The container component.
   */
  protected function mergeLiterals (Component $c)
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

  protected function createParameter ($attrName, $tagName, array $attributes = null, array $bindings = null)
  {
_log("CREATE PARAMETER $tagName");
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

  protected function createSubparameter ($name, $tagName, array $attributes = null, array $bindings = null)
  {
_log("CREATE SUBPARAMETER $name");
    $param              = $this->current;
    $this->current      = $subparam = new Parameter($this->context, $tagName, AttributeType::SRC, $attributes);
    $subparam->bindings = $bindings;
    $param->addChild ($subparam);
    return $subparam;
  }

  protected function canAddParameter ()
  {
    return $this->current != $this->root;
  }

  protected function canAddComponent ()
  {
    return $this->current == $this->root || $this->current instanceof Parameter;
  }

  protected function parseAttributes ($attrStr, array &$attributes = null, array &$bindings = null,
                                      $processBindings = true)
  {
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
