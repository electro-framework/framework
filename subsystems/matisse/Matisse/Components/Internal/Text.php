<?php
namespace Selenia\Matisse\Components\Internal;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Parser\Context;
use Selenia\Matisse\Properties\Base\ComponentProperties;

class TextProperties extends ComponentProperties
{
  public $value = '';

  /**
   * @see PropertiesWithChangeTracking::isModified
   * @return bool
   */
  function isModified ()
  {
    return false;
  }

}

class Text extends Component
{
  protected static $propertiesClass = TextProperties::class;
  /** @var TextProperties */
  public $props;

  public function __construct (Context $context = null, $props = null)
  {
    parent::__construct ();
    if ($context)
      $this->setContext ($context);
//    $this->page = $this;
    $this->setTagName ('Text');
    $this->init ($props);
  }

  public static function from (Context $context = null, $text)
  {
    return new Text($context, ['value' => $text]);
  }

  protected function render ()
  {
    echo $this->props->value;
  }

}
