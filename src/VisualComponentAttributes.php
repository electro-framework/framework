<?php
namespace Selene\Matisse;
use Selene\Matisse\Exceptions\ComponentException;

class VisualComponentAttributes extends ComponentAttributes
{
  public $id;
  public $class;
  public $disabled  = false;
  public $htmlAttrs = '';
  public $hidden    = false;

  protected function typeof_htmlAttrs () { return AttributeType::TEXT; }

  protected function typeof_id () { return AttributeType::ID; }

  protected function typeof_class () { return AttributeType::ID; }

  protected function typeof_disabled () { return AttributeType::BOOL; }

  protected function typeof_hidden () { return AttributeType::BOOL; }

}
