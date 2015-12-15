<?php
namespace Selenia\Matisse\Attributes;

use Selenia\Matisse\AttributeType;

class GenericComponentAttributes extends GenericAttributes
{
  public $content;

  protected function typeof_content () { return AttributeType::SRC; }

}
