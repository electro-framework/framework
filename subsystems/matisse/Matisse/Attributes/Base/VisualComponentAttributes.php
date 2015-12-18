<?php
namespace Selenia\Matisse\Attributes\Base;

use Selenia\Matisse\Attributes\DSL\type;

class VisualComponentAttributes extends ComponentAttributes
{
  /**
   * @var string
   */
  public $class = '';
  /**
   * @var bool
   */
  public $disabled = false;
  /**
   * @var bool
   */
  public $hidden = false;
  /**
   * @var string
   */
  public $htmlAttrs = '';
  /**
   * @var string
   */
  public $id = type::id;

}
