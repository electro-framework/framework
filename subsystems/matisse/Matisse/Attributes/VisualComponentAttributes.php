<?php
namespace Selenia\Matisse\Attributes;

use Selenia\Matisse\Type;

class VisualComponentAttributes extends ComponentAttributes
{
  public $class;
  public $disabled  = false;
  public $hidden    = false;
  public $htmlAttrs = '';
  public $id;

  protected function typeof_class () { return Type::ID; }

  protected function typeof_disabled () { return Type::BOOL; }

  protected function typeof_hidden () { return Type::BOOL; }

  protected function typeof_htmlAttrs () { return Type::TEXT; }

  protected function typeof_id () { return Type::ID; }

}
