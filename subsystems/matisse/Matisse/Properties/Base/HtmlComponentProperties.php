<?php
namespace Selenia\Matisse\Properties\Base;

use Selenia\Matisse\Properties\TypeSystem\type;

/**
 * Properties shared by all {@see HtmlComponent} descendants.
 */
class HtmlComponentProperties extends ComponentProperties
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
  public $id = [type::id, null];

}
