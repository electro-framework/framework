<?php
namespace Selenia\Matisse\Components\Macro;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Parser\Context;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\Macro\MacroInstanceProperties;
use Selenia\Matisse\Properties\Types\type;

class MacroInstance extends Component
{
  protected static $propertiesClass = MacroInstanceProperties::class;

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

  public function parsed ()
  {
    $this->processParameters ();
//    $this->databind ();

    // Move children to default parameter

    if ($this->hasChildren ()) {
      $def = $this->macro->props ()->defaultParam;
      if (!empty($def)) {
        $param = $this->macro->getParameter ($def);
        if (!$param)
          throw new ComponentException($this, "The macro's declared default parameter is invalid: $def");
        $type = $this->props->getTypeOf ($def);
        if ($type != type::content && $type != type::metadata)
          throw new ComponentException($this,
            "The macro's default parameter <b>$def</b> can't hold content (type: " .
            ComponentProperties::$TYPE_NAMES[$type] . ").");
        $param             = new Metadata($this->context, ucfirst ($def), $type);
        $this->props->$def = $param;
        $param->attachTo ($this);
        $param->setChildren ($this->removeChildren ());
      }
    }
    $content = $this->macro->apply ($this);
    $this->replaceBy ($content);
  }

  /**
   * @see IAttributes::attrs()
   * @return MacroInstanceProperties
   */
  function props ()
  {
    return $this->props;
  }

  private function processParameters ()
  {
    $o      = [];
    $styles = $this->props ()->style;
    inspect ("process", $this->props (), $styles);

    if (isset($styles))
      foreach ($styles as $sheet) {
        if (isset($sheet->props ()->src))
          $o[] = [
            'type' => 'sh',
            'src'  => $sheet->props ()->src,
          ];
        else if ($sheet->hasChildren ())
          $o[] = [
            'type' => 'ish',
            'name' => $sheet->props ()->get ('name'),
            'data' => $sheet,
          ];
      }
    $scripts = $this->props ()->script;
    if (isset($scripts)) {
      foreach ($scripts as $script) {
        if (isset($script->props ()->src))
          $o[] = [
            'type' => 'sc',
            'src'  => $script->props ()->src,
          ];
        else if ($script->hasChildren ())
          $o[] = [
            'type'  => 'isc',
            'name'  => $script->props ()->get ('name'),
            'defer' => $script->props ()->get ('defer'),
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
