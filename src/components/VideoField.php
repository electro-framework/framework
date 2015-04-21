<?php
use impactwave\matisse\AttributeType;
use impactwave\matisse\ComponentAttributes;
use impactwave\matisse\VisualComponent;

class VideoFieldAttributes extends ComponentAttributes
{
  public $name;
  public $value;
  public $no_clear     = false;
  public $disabled     = false;
  public $sortable     = false;
  public $preview_value;
  public $image_width  = 160;
  public $image_height = 120;

  protected function typeof_name () { return AttributeType::ID; }
  protected function typeof_value () { return AttributeType::NUM; }
  protected function typeof_no_clear () { return AttributeType::BOOL; }
  protected function typeof_disabled () { return AttributeType::BOOL; }
  protected function typeof_sortable () { return AttributeType::BOOL; }
  protected function typeof_preview_value () { return AttributeType::TEXT; }
  protected function typeof_image_width () { return AttributeType::NUM; }
  protected function typeof_image_height () { return AttributeType::NUM; }
}

class VideoField extends VisualComponent
{

  protected $autoId = true;

  /**
   * Returns the component's attributes.
   * @return VideoFieldAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return VideoFieldAttributes
   */
  public function newAttributes ()
  {
    return new VideoFieldAttributes($this);
  }

  private $videoPlayer;

  protected function render ()
  {
    $this->page->enableFileUpload = true;
    if (isset($this->attrs ()->value)) {
      $this->videoPlayer       = new VideoPlayer($this->context, [
        'value'         => $this->attrs ()->value,
        'preview_value' => $this->attrs ()->preview_value,
        'class'         => 'VideoField_preview'
      ], [
        'width'  => $this->attrs ()->image_width,
        'height' => $this->attrs ()->image_height
      ]);
      $this->videoPlayer->page = $this->page;
    }

    $this->beginTag ('input');
    $this->addAttribute ('type', 'hidden');
    $this->addAttribute ('id', "{$this->attrs()->id}Field");
    if (isset($this->attrs ()->name))
      $this->addAttribute ('name', $this->attrs ()->name);
    else $this->addAttribute ('name', $this->attrs ()->id);
    $this->addAttribute ('value', $this->attrs ()->value);
    $this->endTag ();

    $this->beginTag ('div', [
      'class' => 'videoPlaceholder',
      'style' => enum (';',
        concat ('width:', $this->attrs ()->image_width),
        concat ('height:', $this->attrs ()->image_height)
      )
    ]);

    $this->beginContent ();
    if (isset($this->videoPlayer))
      $this->videoPlayer->doRender ();
    else $this->addTag ('div', [
      'class' => 'emptyVid'
    ]);

    $this->endTag ();

    $this->beginTag ('div');
    $this->addAttribute ('class', 'buttons');

    $this->beginTag ('div');
    $this->addAttribute ('class', 'fileBtn');
    $this->beginContent ();

    $button = new Button($this->context, [
      'disabled' => $this->attrs ()->disabled,
      'class'    => 'VideoField_browse'
    ]);
    $this->runPrivate ($button);

    $this->addTag ('input', [
      'id'        => "{$this->attrs()->id}File",
      'type'      => 'file',
      'class'     => 'fileBtn',
      'size'      => 1,
      'tabindex'  => -1,
      'onchange'  => "VideoField_onChange('{$this->attrs()->id}')",
      'name'      => ifset ($this->attrs ()->name, $this->attrs ()->name . '_file', 'file'),
      'hidefocus' => $this->page->browserIsIE ? 'true' : null
    ]);

    $this->endTag ();

    if (!$this->attrs ()->no_clear) {
      $button = new Button($this->context, [
        'id'       => "{$this->attrs()->id}Clear",
        'script'   => "VideoField_clear('{$this->attrs()->id}')",
        'disabled' => $this->attrs ()->disabled || !isset($this->attrs ()->value),
        'class'    => 'VideoField_clear'
      ]);
      $this->runPrivate ($button);
    }
    if ($this->attrs ()->sortable) {
      $button = new Button($this->context, [
        'action'   => 'down',
        'param'    => $this->attrs ()->value,
        'disabled' => $this->attrs ()->disabled || !isset($this->attrs ()->value),
        'class'    => 'VideoField_next'
      ]);
      $this->runPrivate ($button);

      $button = new Button($this->context, [
        'action'   => 'up',
        'param'    => $this->attrs ()->value,
        'disabled' => $this->attrs ()->disabled || !isset($this->attrs ()->value),
        'class'    => 'VideoField_prev'
      ]);
      $this->runPrivate ($button);
    }
    echo '</div><div class="end"></div>';
  }
}

