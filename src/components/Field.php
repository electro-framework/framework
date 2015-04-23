<?php
use selene\matisse\AttributeType;
use selene\matisse\ComponentAttributes;
use selene\matisse\VisualComponent;

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
    /** @var Parameter $inputFld */
    $inputFld = $this->getChildren ('field');
    if (count ($inputFld) != 1)
      throw new ComponentException($this, "<b>field</b> parameter must define <b>one</b> component instance.", true);

    $this->beginContent ();
    /** @var Component $input */
    $input = $inputFld[0];

    $name = $this->attrs ()->get ('name');
    if (empty($name))
      throw new ComponentException($this, "<b>name</b> parameter is required.");

    $fldId = $input->attrs ()->get ('id', $name);

    if ($input instanceof HtmlEditor) {
      $forId = $fldId . '_field';
      $click = "$('#$fldId .redactor_editor').focus()";
    } else {
      $forId = $fldId;
      $click = null;
    }

    // LABEL

    $label = $this->attrs ()->label;
    if (!empty($label))
      $this->addTag ('label', [
        'class'   => 'control-label ' . $this->attrs ()->label_width,
        'for'     => $forId,
        'onclick' => $click
      ], $label);

    $this->beginTag ('div', [
      'class' => $this->attrs ()->width
    ]);
    $this->beginContent ();

    // EMBEDDED COMPONENT

    $input->attrsObj->css_class .= ' form-control';
    $input->attrsObj->id   = $fldId;
    $input->attrsObj->name = $name;
    $input->doRender ();

    $this->endTag ();
  }
}

