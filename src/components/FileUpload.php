<?php
use Selene\Matisse\AttributeType;
use Selene\Matisse\ComponentAttributes;
use Selene\Matisse\VisualComponent;

class FileUploadAttributes extends ComponentAttributes
{
  public $name;
  public $value;
  public $no_clear = false;
  public $disabled = false;

  protected function typeof_name () { return AttributeType::ID; }
  protected function typeof_value () { return AttributeType::TEXT; }
  protected function typeof_no_clear () { return AttributeType::BOOL; }
  protected function typeof_disabled () { return AttributeType::BOOL; }
}

class FileUpload extends VisualComponent
{

  protected $autoId = true;

  /**
   * Returns the component's attributes.
   * @return FileUploadAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return FileUploadAttributes
   */
  public function newAttributes ()
  {
    return new FileUploadAttributes($this);
  }

  protected function render ()
  {
    $this->page->enableFileUpload = true;
    $value                        = $this->attrs ()->get ('value', '');

    $this->beginTag ('input');
    $this->addAttribute ('type', 'hidden');
    $this->addAttribute ('id', "{$this->attrs()->id}Field");
    if (isset($this->attrs ()->name))
      $this->addAttribute ('name', $this->attrs ()->name);
    else $this->addAttribute ('name', $this->attrs ()->id);
    $this->addAttribute ('value', $value);
    $this->endTag ();

    $inputFld = new Input($this->context, [
      'id'        => "{$this->attrs()->id}Input",
      'value'     => empty($value) ? '' : Media::getOriginalFileName ($value),
      'class'     => 'FileUpload_input',
      'read_only' => true
    ]);
    $this->runPrivate ($inputFld);

    $this->beginTag ('div');
    $this->addAttribute ('class', 'fileBtn');
    $this->beginContent ();

    $button = new Button($this->context, [
      'disabled' => $this->attrs ()->disabled,
      'class'    => 'FileUpload_browse'
    ]);
    $this->runPrivate ($button);

    $this->addTag ('input', [
      'id'       => "{$this->attrs()->id}File",
      'type'     => 'file',
      'class'    => 'fileBtn',
      'size'     => 1,
      'tabindex' => -1,
      'onchange' => "FileUpload_onChange('{$this->attrs()->id}')",
      'name'     => ifset ($this->attrs ()->name, $this->attrs ()->name . '_file', 'file')
    ]);

    $this->endTag ();

    if (!$this->attrs ()->no_clear) {
      $button = new Button($this->context, [
        'id'       => "{$this->attrs()->id}Clear",
        'script'   => "FileUpload_clear('{$this->attrs()->id}')",
        'disabled' => $this->attrs ()->disabled || !isset($this->attrs ()->value),
        'class'    => 'FileUpload_clear'
      ]);
      $this->runPrivate ($button);
    }

    $this->addTag ('div', [
      'class' => 'end'
    ]);
  }
}

