<?php
use impactwave\matisse\AttributeType;
use impactwave\matisse\ComponentAttributes;

class VideoPlayerAttributes extends ComponentAttributes
{
  public $auto_load_youtube_preview_image = true;
  public $auto_play                       = false;
  public $auto_repeat                     = false;
  public $mouse_wheel                     = 'volume';
  public $prefer_higher_quality           = true;
  public $preview_image_resize_mode       = 'fit into component area / mind aspect ratio';
  public $preview_image_url;
  public $url;
  public $video_resize_mode               = 'fit into component area / mind aspect ratio';
  public $volume                          = 75;
  public $youtube_proxy;                  //don't set it here
  public $value;
  public $preview_value;
  public $window_mode;
  public $skin                            = 'Dark';
  public $width                           = 320;
  public $height                          = 240;

  protected function typeof_auto_load_youtube_preview_image () { return AttributeType::BOOL; }
  protected function typeof_auto_play () { return AttributeType::BOOL; }
  protected function typeof_auto_repeat () { return AttributeType::BOOL; }
  protected function typeof_mouse_wheel () { return AttributeType::TEXT; }
  protected function typeof_prefer_higher_quality () { return AttributeType::BOOL; }
  protected function typeof_preview_image_resize_mode () { return AttributeType::TEXT; }
  protected function typeof_preview_image_url () { return AttributeType::TEXT; }
  protected function typeof_url () { return AttributeType::TEXT; }
  protected function typeof_video_resize_mode () { return AttributeType::TEXT; }
  protected function typeof_volume () { return AttributeType::NUM; }
  protected function typeof_youtube_proxy () { return AttributeType::TEXT; }
  protected function typeof_value () { return AttributeType::TEXT; }
  protected function typeof_preview_value () { return AttributeType::TEXT; }
  protected function typeof_window_mode () { return AttributeType::ID; }
  protected function typeof_dark () { return AttributeType::TEXT; }
  protected function typeof_width () { return AttributeType::NUM; }
  protected function typeof_height () { return AttributeType::NUM; }

  protected function enum_window_mode () { return ['window', 'opaque', 'transparent']; }

  protected function enum_mouseWheel ()
  {
    return [
      'none',
      'seek',
      'volume'
    ];
  }

  protected function enum_videoResizeMode ()
  {
    return [
      'fit into component area / mind aspect ratio',
      'fit into component area',
      'fill out component area',
      'center in component area',
      'center in component area / shrink if necessary',
      'resize component to video size'
    ];
  }

  protected function enum_previewImageResizeMode ()
  {
    return $this->enum_video_resize_mode ();
  }
}

class VideoPlayer extends FlashComponent
{

  private $isSWF = false;

  /** Overriden */
  protected $baseStyleName = 'VideoPlayer';

  /** Overriden */
  protected function stylesToFlashVars ()
  {
    return $this->isSWF
      ? null
      : [
        'show_full_screen_button' => 'showFullScreenButton'
      ];
  }

  /** Overriden */
  protected function attributesToFlashVars ()
  {
    return $this->isSWF
      ? null
      : [
        'auto_load_youtube_preview_image' => 'autoLoadYouTubePreviewImage',
        'auto_play'                       => 'autoPlay',
        'auto_repeat'                     => 'autoRepeat',
        'mouse_wheel'                     => 'mouseWheel',
        'prefer_higher_quality'           => 'preferHigherQuality',
        'preview_image_resize_mode'       => 'previewImageResizeMode',
        'preview_image_url'               => 'previewImageUrl',
        'url'                             => 'url',
        'video_resize_mode'               => 'videoResizeMode',
        'volume'                          => 'volume',
        'youtube_proxy'                   => 'youTubeProxy'
      ];
  }

  /**
   * Returns the component's attributes.
   * @return VideoPlayerAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return VideoPlayerAttributes
   */
  public function newAttributes ()
  {
    return new VideoPlayerAttributes($this);
  }

  protected function setup ()
  {
    global $application;
    if (isset($this->attrs ()->value)) {
      $url                 = Media::getFileURI ($this->attrs ()->value);
      $this->attrs ()->url = $url;
    } else $url = $this->attrs ()->url;
    if (isset($this->attrs ()->preview_value)) {
      $preview_url                       = Media::getImageURI ($this->attrs ()->preview_value);
      $this->attrs ()->preview_image_url = $preview_url;
    } else $url = $this->attrs ()->url;
    switch (strtolower (Media::getExt ($url))) {
      case 'swf':
        $this->flashURL = $url;
        $this->isSWF    = true;
        break;
      //case 'flv':
      default:
        $baseURI        = $application->getAddonURI ('components/VideoPlayer') . '/';
        $this->flashURL = $baseURI . $this->attrs ()->skin . '.swf';
        if (!isset($this->attrs ()->youtube_proxy))
          $this->attrs ()->youtube_proxy = $baseURI . 'YouTubeProxy.php';
        break;
    }
    $this->windowMode = property ($this->attrs (), 'window_mode', 'window');
    parent::setup ();
    if (isset($this->attrs ()->width) || isset($this->attrs ()->height)) {
      $styles = "#{$this->attrs()->id}{";
      if (isset($this->attrs ()->width))
        $styles .= "width:{$this->attrs()->width};";
      if (isset($this->attrs ()->height))
        $styles .= "height:{$this->attrs()->height};";
      $styles .= '}';
      $this->page->addInlineCss ($styles);
    }
  }

}
