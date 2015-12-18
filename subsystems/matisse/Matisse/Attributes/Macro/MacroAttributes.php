<?php
namespace Selenia\Matisse\Attributes\Macro;

use Selenia\Matisse\Attributes\Base\ComponentAttributes;
use Selenia\Matisse\Attributes\DSL\type;
use Selenia\Matisse\Components\Internal\Parameter;

class MacroAttributes extends ComponentAttributes
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
   * @var Parameter[]
   */
  public $param = type::multipleParams;
  /**
   * @var Parameter[]
   */
  public $script = type::multipleParams;
  /**
   * @var Parameter[]
   */
  public $style = type::multipleParams;
}
