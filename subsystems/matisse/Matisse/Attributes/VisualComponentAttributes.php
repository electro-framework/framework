<?php
namespace Selenia\Matisse\Attributes;

use Selenia\Matisse\AttributeType;

class VisualComponentAttributes extends ComponentAttributes
{
  public $class;
  public $disabled  = false;
  public $hidden    = false;
  public $htmlAttrs = '';
  public $id;

  protected function typeof_class () { return AttributeType::ID; }

  protected function typeof_disabled () { return AttributeType::BOOL; }

  protected function typeof_hidden () { return AttributeType::BOOL; }

  protected function typeof_htmlAttrs () { return AttributeType::TEXT; }

  protected function typeof_id () { return AttributeType::ID; }

}
