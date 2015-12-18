<?php
namespace Selenia\Matisse\Components\Macro;

use Selenia\Matisse\Attributes\Base\ComponentAttributes;
use Selenia\Matisse\Attributes\DSL\type;
use Selenia\Matisse\Attributes\Macro\MacroInstanceAttributes;
use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Parameter;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Interfaces\IAttributes;
use Selenia\Matisse\Parser\Context;

class MacroInstance extends Component implements IAttributes
{
  public $allowsChildren = true;

  /**
   * Points to the component that defines the macro for this instance.
   * @var Macro
   */
  protected $macro;

  public function __construct (Context $context, $tagName, Macro $macro, array $attributes = null)
  {
    $this->macro = $macro; //must be defined before the parent constructor is called
    parent::__construct ($context, $attributes);
    $this->setTagName ($tagName);
  }

  /**
   * @see IAttributes::attrs()
   * @return MacroInstanceAttributes
   */
  function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * @see IAttributes::newAttributes()
   * @return MacroInstanceAttributes
   */
  function newAttributes ()
  {
    return new MacroInstanceAttributes($this, $this->macro);
  }

  public function parsed ()
  {
    $this->processParameters ();
//    $this->databind ();

    // Move children to default parameter

    if ($this->hasChildren ()) {
      $def = $this->macro->attrs ()->defaultParam;
      if (!empty($def)) {
        $param = $this->macro->getParameter ($def);
        if (!$param)
          throw new ComponentException($this, "The macro's declared default parameter is invalid: $def");
        $type = $this->attrsObj->getTypeOf ($def);
        if ($type != type::parameter && $type != type::metadata)
          throw new ComponentException($this,
            "The macro's default parameter <b>$def</b> can't hold content (type: " .
            ComponentAttributes::$TYPE_NAMES[$type] . ").");
        $param                = new Parameter($this->context, ucfirst ($def), $type);
        $this->attrsObj->$def = $param;
        $param->attachTo ($this);
        $param->setChildren ($this->removeChildren ());
      }
    }
    $content = $this->macro->apply ($this);
    $this->replaceBy ($content);
  }

  private function processParameters ()
  {
    $o      = [];
    $styles = $this->attrs ()->style;

    if (isset($styles))
      foreach ($styles as $sheet) {
        if (isset($sheet->attrs ()->src))
          $o[] = [
            'type' => 'sh',
            'src'  => $sheet->attrs ()->src,
          ];
        else if (!empty($sheet->children))
          $o[] = [
            'type' => 'ish',
            'name' => $sheet->attrs ()->get ('name'),
            'data' => $sheet,
          ];
      }
    $scripts = $this->attrs ()->script;
    if (isset($scripts)) {
      foreach ($scripts as $script) {
        if (isset($script->attrs ()->src))
          $o[] = [
            'type' => 'sc',
            'src'  => $script->attrs ()->src,
          ];
        else if (!empty($script->children))
          $o[] = [
            'type'  => 'isc',
            'name'  => $script->attrs ()->get ('name'),
            'defer' => $script->attrs ()->get ('defer'),
            'data'  => $script,
          ];
      }
    }
    $o = array_reverse ($o);
    foreach ($o as $i)
      switch ($i['type']) {
        case 'sh':
          $this->page->addStylesheet ($i['src'], true);
          break;
        case 'ish':
          $this->page->addInlineCss ($i['data'], $i['name'], true);
          break;
        case 'sc':
          $this->page->addScript ($i['src'], true);
          break;
        case 'isc':
          if ($i['defer'])
            $this->page->addInlineDeferredScript ($i['data'], $i['name'], true);
          else $this->page->addInlineScript ($i['data'], $i['name'], true);
          break;
      }
  }

}
