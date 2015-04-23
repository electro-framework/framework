<?php
use impactwave\matisse\AttributeType;
use impactwave\matisse\ComponentAttributes;
use impactwave\matisse\VisualComponent;

//Note that the file fckeditor/editor/fckeditor.html should be changed from the default to:  <body style="visibility:hidden">

class HtmlEditorAttributes extends ComponentAttributes
{
  public $name;
  public $value;
  public $lang;
  public $autofocus = false;

  protected function typeof_name () { return AttributeType::ID; }
  protected function typeof_lang () { return AttributeType::ID; }
  protected function typeof_autofocus () { return AttributeType::BOOL; }
  protected function typeof_value () { return AttributeType::TEXT; }
}

class HtmlEditor extends VisualComponent
{

  protected $autoId = true;

  /**
   * Returns the component's attributes.
   * @return HtmlEditorAttributes
   */
  public function attrs ()
  {
    return $this->attrsObj;
  }

  /**
   * Creates an instance of the component's attributes.
   * @return HtmlEditorAttributes
   */
  public function newAttributes ()
  {
    return new HtmlEditorAttributes($this);
  }

  /**
   * @global Application $application
   */
  protected function render ()
  {
    global $application, $controller;
    if (!isset($this->attrs ()->name))
      $this->attrs ()->name = $this->attrs ()->id;
    $lang           = property ($this->attrs (), 'lang', $controller->lang);
    $lang           = $lang === 'pt' ? 'pt_pt' : $lang;
    $addonURI       = "$application->addonsPath/components/redactor";
    $autofocus          = $this->attrs ()->autofocus ? 'true' : 'false';
    $scriptsBaseURI = $application->framework;
    $initCode       = <<<JAVASCRIPT
var redactorToolbar = ['html', 'formatting', 'bold', 'italic',
'unorderedlist', 'orderedlist', 'outdent', 'indent',
'image', 'video', 'file', 'table', 'link',
'fontcolor', 'backcolor',
'alignment',
'horizontalrule', 'fullscreen'];
JAVASCRIPT;
    $code           = <<<JAVASCRIPT
$(document).ready(
  function() {
    $('#{$this->attrs ()->id}_field').redactor({
      buttons: redactorToolbar,
      lang: '{$lang}',
      focus: $autofocus,
      resize: false,
      autoresize: false,
      minHeight: 220,
      plugins: ['video', 'table', 'fullscreen', 'fontcolor', 'imagemanager', 'filemanager'],
      imageUpload: '$scriptsBaseURI/imageUpload.php',
      fileUpload: '$scriptsBaseURI/fileUpload.php',
      imageGetJson: '$scriptsBaseURI/gallery.php',
      imageManagerJson: '$scriptsBaseURI/gallery.php',
      imageInsertCallback: onInlineImageInsert
    });
  }
);
JAVASCRIPT;
    $this->page->addScript ("$addonURI/redactor.min.js");
    $this->page->addScript ("$addonURI/langs/$lang.js");
    $this->page->addStylesheet ("$addonURI/css/redactor.css");
    $this->page->addScript ("$addonURI/plugins/fontcolor.js");
    $this->page->addScript ("$addonURI/plugins/video.js");
    $this->page->addScript ("$addonURI/plugins/table.js");
    $this->page->addScript ("$addonURI/plugins/fullscreen.js");
    $this->page->addScript ("$addonURI/plugins/imagemanager.js");
    $this->page->addInlineScript ($initCode, 'redactor');
    $this->page->addInlineScript ($code);

    $this->addTag ('textarea', [
      'id'   => $this->attrs ()->id . "_field",
      'name' => $this->attrs ()->name
    ], $this->attrs ()->value);
  }
}
