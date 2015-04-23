<?php
namespace selene\matisse;
use selene\matisse\components\Literal;
use selene\matisse\components\Page;
use selene\matisse\components\Parameter;
use selene\matisse\components\TemplateInstance;
use selene\matisse\exceptions\FileIOException;
use selene\matisse\exceptions\ParseException;

class Parser
{
  const PARSE_TAG            = '#(<)(/?)([cpt]):([\w\-]+)\s*(.*?)(/?)(>)#s';
  const PARSE_DATABINDINGS   = '#\{(?=\S) ( [^{}]* | \{[^{}]*\} )* \}#x';
  const TRIM_LITERAL_CONTENT = '#^\s+|(?<=\>)\s+(?=\s)|(?<=\s)\s+(?=\<)|\s+$#';
  const TRIM_LEFT_CONTENT    = '#^\s+|(?<=\>)\s+(?=\s)#';
  const TRIM_RIGHT_CONTENT   = '#(?<=\s)\s+(?=\<)|\s+$#';
  const PARSE_PARAMS         = '#([\w\-\:]+)\s*=\s*"(.*?)(")#s';
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
      list(, list(, $start), list($term), list($namespace), list($tag), list($attrs), list($term2), list(, $end)
        ) = $match;
      //literal content
      if ($start > $pos) {
        $v = trim (substr ($body, $pos, $start - $pos));
        if (!empty($v)) {
          if ($this->scalarParam) $this->current->setScalar ($v);
          else {
            if (!$this->canAddComponent ()) {
              if (!isset($this->current->defaultAttribute)) {
                $s = join ('</b>, <b>', $this->current->attrs ()->getAttributeNames ());
                throw new ParseException("You may not define literal content at this.location.\nExpected parameters: <b>$s</b>.",
                  $body, $pos, $start);
              }
              $this->generateImplicitParameter ();
            }
            $this->processLiteral ($v);
          }
        }
      }
      //process tag
      if ($term) {
        //close previous tag
        if ($namespace != $this->current->namespace || $tag != $this->current->getTagName ()) {
          $parent = $this->current->parent;
          if (!($this->current instanceof Parameter && $this->current->isImplicit &&
                $namespace == $parent->namespace && $tag == $parent->getTagName ())
          )
            throw new ParseException("Closing tag &lt;/$namespace:$tag&gt; does not match the opening tag &lt;{$this->current->getQualifiedName ()}&gt;.",
              $body, $start, $end);
        }
        if ($attrs) throw new ParseException('Closing tags must not have attributes.', $body, $start, $end);
        $this->tagComplete ();
      } else {
        //new tag
        if ($this->scalarParam)
          throw new ParseException("Invalid value for a scalar parameter.", $body, $start, $end);
        $attributes = $bindings = null;
        switch ($namespace) {
          case 'c':
            if (!$this->canAddComponent ()) {
              if (!isset($this->current->defaultAttribute))
                throw new ParseException('You may not define a component at this location.', $body,
                  $start, $end);
              $this->generateImplicitParameter ();
            }
            /** @var Parameter|boolean $defParam */
            $this->parseAttributes ($attrs, $attributes, $bindings);
            $component = Component::create ($this->context, $tag, $attributes);
            $component->setTagName ($tag); //for performance
            $component->bindings = $bindings;
            $this->addComponent ($component);
            break;
          case 'p':
            if (!$this->canAddParameter ())
              throw new ParseException('Parameters must be specified as direct descendant nodes of a component.',
                $body, $start, $end);
            $name = normalizeAttributeName ($tag);
            if (!$this->current instanceof Parameter) {
              //create parameter
              if (!$this->current->supportsAttributes)
                throw new ParseException('The component does not support parameters.', $body, $start,
                  $end);
              $this->parseAttributes ($attrs, $attributes, $bindings);
              if (!$this->current->attrs ()->defines ($name)) {
                $s = join ('</b>, <b>', $this->current->attrs ()->getAttributeNames ());
                throw new ParseException("The component does not support the specified parameter <b>$tag</b>.\nExpected: <b>$s</b>.",
                  $body, $start, $end);
              }
              $this->createParameter ($name, $tag, $attributes, $bindings);
            } else {
              //create subparameter
              $this->parseAttributes ($attrs, $attributes, $bindings);
              $this->createSubparameter ($name, $tag, $attributes, $bindings);
            }
            break;
          case 't':
            $template = $this->context->getTemplate ($tag);
            if (is_null ($template))
              try {
                $template = $this->loadTemplate ($tag);
              } catch (FileIOException $e) {
                throw new ParseException("Template <b>$tag</b> was not found.\n" . $e->getMessage (),
                  $body, $start, $end);
              }
            $this->parseAttributes ($attrs, $attributes, $bindings, true);
            $component           = new TemplateInstance($this->context, $tag, $template, $attributes);
            $component->bindings = $bindings;
            $this->addComponent ($component);
            break;
        }
        //short tag
        if ($term2)
          $this->tagComplete ();
      }
      $pos = $end + 1;
    }
    $nextContent = substr ($body, $pos);
    if (strlen ($nextContent)) $this->processLiteral (trim ($nextContent));
  }

  protected function generateImplicitParameter ()
  {
    $current = $this->current;
    $defName = $current->defaultAttribute;
    // Synthesise a parameter only when no corresponding one already exists.
    if (is_null ($current->attrs ()->$defName))
      $this->createParameter (normalizeAttributeName ($defName), $defName)->isImplicit = true;

  }

  protected function loadTemplate ($tagName)
  {
    global $application, $FRAMEWORK;
    $filename = normalizeTagName ($tagName) . '.xml';
    $path     = "$application->templatesPath/$filename";
    if (fileExists ($path))
      $content = @file_get_contents ($path, true);
    else {
      $path = "$FRAMEWORK/templates/$filename";
      if (fileExists ($path))
        $content = @file_get_contents ($path, true);
      else throw new FileIOException($filename);
    }
    $content = extractXML ($content);
    $parser  = new Parser($this->context);
    $parser->parse ($content, $this->root);
    $template = $this->context->getTemplate ($tagName);
    if (isset($template)) {
      $template->remove ();
      return $template;
    }
    throw new ParseException("File <b>$filename</b> does not define a template named <b>$tagName</b>.");
  }

  protected function tagComplete ()
  {
    $current = $this->current;
    if ($this->scalarParam)
      $this->scalarParam = false;
    $parent = $current->parent;
    $current->parsed (); //calling this method may unset the 'parent' property.
    $this->current = $parent; //also discards the current scalar parameter, if that is the case.

    // Closing a component's tag must also close an implicit parameter.
    if (isset($current->isImplicit))
      $this->tagComplete ();
  }

  protected function createParameter ($name, $tagName, array $attributes = null, array $bindings = null)
  {
    $component     = $this->current;
    $type          = $component->attrs ()->getTypeOf ($name);
    $this->current = $param = new Parameter($this->context, $tagName, $type, $attributes);
    $param->attachTo ($component);
    switch ($type) {
      case AttributeType::SRC:
        $component->attrs ()->$name = $param;
        $param->bindings            = $bindings;
        break;
      case AttributeType::PARAMS:
        if (isset($component->attrs ()->$name))
          $component->attrs ()->{$name}[] = $param;
        else $component->attrs ()->$name = [$param];
        $param->bindings = $bindings;
        break;
      default:
        $this->scalarParam = true;
    }
    return $param;
  }

  protected function createSubparameter ($name, $tagName, array $attributes = null, array $bindings = null)
  {
    $param              = $this->current;
    $this->current      = $subparam = new Parameter($this->context, $tagName, AttributeType::SRC, $attributes);
    $subparam->bindings = $bindings;
    $param->addChild ($subparam);
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
      while (preg_match (self::PARSE_PARAMS, $attrStr, $match, PREG_OFFSET_CAPTURE, $sPos)) {
        list(, list($key), list($value), list(, $next)) = $match;
        if (substr ($key, 0, 6) == 'style:') {
          $key                  = substr ($key, 6);
          $attributes['styles'] = strJoin (get ($attributes, 'styles', ''), "$key:$value", ';');
        } else {
          if ($processBindings && strpos ($value, '{') !== false)
            $bindings[renameAttribute ($key)] = $value;
          else if ($key == 'styles')
            $attributes['styles'] = strJoin (get ($attributes, 'styles', ''), $value, ';');
          else $attributes[renameAttribute ($key)] = $value;
        }
        $sPos = $next + 1;
      }
    }
  }

  private function addComponent (Component $instance)
  {
    $this->current->addChild ($instance);
    $this->current = $instance;
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
      } else $lit = new Literal($this->context, $v);
      $this->current->addChild ($lit);
    }
  }
}
