<?php

use Selene\Matisse\AttributeType;
use Selene\Matisse\Component;
use Selene\Matisse\ComponentAttributes;
use Selene\Matisse\Exceptions\ComponentException;
use Selene\Matisse\VisualComponent;

class FieldAttributes extends ComponentAttributes
{
  public $name;
  public $label;
  public $field;
  public $label_width = 'col-sm-4 col-md-3';
  public $width       = 'col-sm-8 col-md-7 col-lg-6';

  protected function typeof_name () { return AttributeType::ID; }

  protected function typeof_label () { return AttributeType::TEXT; }

  protected function typeof_field () { return AttributeType::SRC; }

  protected function typeof_width () { return AttributeType::TEXT; }

  protected function typeof_label_width () { return AttributeType::TEXT; }
}

class Field extends VisualComponent
{
  public $cssClassName = 'form-group';

  public $defaultAttribute = 'field';

  /**
   * Returns the component's attributes.
   * @return FieldAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return FieldAttributes
   */
  public function newAttributes ()
  {
    return new FieldAttributes($this);
  }

  protected function render ()
  {
    $inputFlds = $this->getChildren ('field');
    if (empty ($inputFlds))
      throw new ComponentException($this, "<b>field</b> parameter must define <b>one or more</b> component instances.",
        true);

    $name = $this->attrs ()->get ('name');
    if (empty($name))
      throw new ComponentException($this, "<b>name</b> parameter is required.");

    // Treat the first child component specially

    /** @var Component $input */
    $input = $inputFlds[0];

    $fldId = $input->attrs ()->get ('id', $name);

    if ($input instanceof HtmlEditor) {
      $forId = $fldId . '0_field';
      $click = "$('#{$fldId}0 .redactor_editor').focus()";
    }
    else {
      $forId = $fldId . '0';
      $click = null;
    }

    $this->beginContent ();

    // Output a LABEL

    $label = $this->attrs ()->label;
    if (!empty($label))
      $this->addTag ('label', [
        'class'   => 'control-label ' . $this->attrs ()->label_width,
        'for'     => $forId,
        'onclick' => $click
      ], $label);

    // Output child components

    $this->beginTag ('div', [
      'class' => $this->attrs ()->width
    ]);
    $this->beginContent ();

    foreach ($inputFlds as $i => $input) {

      // EMBEDDED COMPONENTS

      $input->attrsObj->css_class .= ' form-control';
      $input->attrsObj->id   = "$fldId$i";
      $input->attrsObj->name = $name;
      $input->doRender ();
    }

    $this->endTag ();
  }
}

