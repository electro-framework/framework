<?php

use Selene\Matisse\AttributeType;
use Selene\Matisse\Component;
use Selene\Matisse\ComponentAttributes;
use Selene\Matisse\Components\Literal;
use Selene\Matisse\Exceptions\ComponentException;
use Selene\Matisse\VisualComponent;

class FieldAttributes extends ComponentAttributes
{
  public $name;
  public $label;
  public $field;
  public $label_width = 'col-sm-4 col-md-3';
  public $width       = 'col-sm-8 col-md-7 col-lg-6';
  /**
   * Bootstrap form field grouo addon
   * @var string
   */
  public $prepend;
  /**
   * Bootstrap form field grouo addon
   * @var string
   */
  public $append;

  protected function typeof_name () { return AttributeType::ID; }

  protected function typeof_label () { return AttributeType::TEXT; }

  protected function typeof_field () { return AttributeType::SRC; }

  protected function typeof_width () { return AttributeType::TEXT; }

  protected function typeof_label_width () { return AttributeType::TEXT; }

  protected function typeof_prepend () { return AttributeType::SRC; }

  protected function typeof_append () { return AttributeType::SRC; }
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
    $input   = $inputFlds[0];
    $append  = $this->getChildren ('append');
    $prepend = $this->getChildren ('prepend');

    $fldId = $input->attrs ()->get ('id', $name);

    if ($input->className == 'HtmlEditor') {
      $forId = $fldId . '0_field';
      $click = "$('#{$fldId}0 .redactor_editor').focus()";
    }
    else {
      $forId = $fldId . '0';
      $click = null;
    }
    if ($input->className == 'Input')
      switch ($input->attrs ()->type) {
        case 'date':
        case 'datetime':
//          $btn       = self::create ($this->context, 'button', ['class' => 'btn btn-default', 'icon' => 'glyphicon glyphicon-calendar']);
//          $btn->page = $this->page;
//          $append = [$btn];
        $append = [Literal::from ($this->context, '<i class="glyphicon glyphicon-calendar"></i>')];
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
      'class' => enum (' ', when ($append || $prepend, 'input-group'), $this->attrs ()->width)
    ]);
    $this->beginContent ();

    if ($prepend) $this->renderAddOn ($prepend[0]);

    foreach ($inputFlds as $i => $input) {

      // EMBEDDED COMPONENTS

      $input->attrsObj->css_class .= ' form-control';
      $input->attrsObj->id   = "$fldId$i";
      $input->attrsObj->name = $name;
      $input->doRender ();
    }

    if ($append) $this->renderAddOn ($append[0]);

    $this->endTag ();
  }

  private function renderAddOn (Component $addOn)
  {
    switch ($addOn->getTagName ()) {
      case 'literal':
      case 'checkbox':
      case 'radiobutton':
        echo '<span class="input-group-addon">';
        $addOn->doRender ();
        echo '</span>';
        break;
      case 'button':
        echo '<span class="input-group-btn">';
        $addOn->doRender ();
        echo '</span>';
        break;
    }

  }
}

