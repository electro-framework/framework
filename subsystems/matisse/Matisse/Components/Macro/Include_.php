<?php
namespace Selenia\Matisse\Components\Macro;

use Selenia\Matisse\Parser\Context;
use Selenia\Matisse\Properties\Macro\MacroInstanceProperties;

class IncludeProperties extends MacroInstanceProperties
{
  /**
   * The name of the macro to be loaded.
   * @var string
   */
  public $name = '';
}

class Include_ extends MacroInstance
{
  public function __construct (Context $context, array $properties = null)
  {
    $macro     = self::getMacro ($context, $parent, $tagName);
    parent::__construct($context, $this->getTagName(), $macro, $properties);
  }
}
