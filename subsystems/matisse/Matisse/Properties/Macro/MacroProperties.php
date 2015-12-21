<?php
namespace Selenia\Matisse\Properties\Macro;

use Selenia\Matisse\Components\Internal\ContentProperty;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\Types\type;

class MacroProperties extends ComponentProperties
{
  /**
   * @var string
   */
  public $defaultParam = type::id;
  /**
   * @var string
   */
  public $name = type::id;
  /**
   * @var ContentProperty[]
   */
  public $param = type::collection;
  /**
   * @var ContentProperty[]
   */
  public $script = type::collection;
  /**
   * @var ContentProperty[]
   */
  public $style = type::collection;
}
