<?php
namespace Selenia\Matisse\Components\Internal;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Parser\Context;

class HtmlDocument_old extends Component
{
  public $author   = '';
  public $browserIsChrome  = false;
  public $browserIsEdge    = false;
  public $browserIsFF      = false;
  public $browserIsIE      = false;
  public $browserIsIE10    = false;
  public $browserIsIE11    = false;
  public $browserIsIE9     = false;
  public $browserIsSafari  = false;
  public $charset          = 'UTF-8';
  public $clientIsWindows  = false;
  public $debugMode        = false;
  public $defaultAction;
  public $description      = '';
  public $doctype          = '<!DOCTYPE HTML>';
  public $enableFileUpload = false;
  public $footer           = '';
  public $formAutocomplete = false;
  public $keywords = '';
  /** Content to be inserted before the form element. */
  public $preForm = '';
  public $requestURI;
  public $targetURL;
  public $title;

  function __construct (Context $context)
  {
    parent::__construct ($context);
    $this->root = $this;
    $this->setTagName ('Page');
    $this->checkBrowser ();
    $this->requestURI = $_SERVER['REQUEST_URI'];
  }

  function checkBrowser ()
  {
    $b = get ($_SERVER, 'HTTP_USER_AGENT', '');
    if (preg_match ('#MSIE (\d+)#', $b, $match)) {
      $v                   = intval ($match[1]);
      $this->browserIsIE10 = $v == 10;
      $this->browserIsIE9  = $v >= 9;
      $this->browserIsIE   = true;
    }
    if (strpos ($b, 'Trident/7.0; rv:11') !== false)
      $this->browserIsIE = $this->browserIsIE11 = true;
    if (strpos ($b, 'Edge/') !== false)
      $this->browserIsIE = $this->browserIsEdge = true;
    $this->browserIsFF     = strpos ($b, 'Gecko/') !== false;
    $this->browserIsSafari = strpos ($b, 'Safari') !== false && strpos ($b, 'Chrome') === false;
    $this->browserIsChrome = strpos ($b, 'Chrome') !== false;
    $this->clientIsWindows = strpos ($b, 'Windows') !== false;
  }

  function debugHeaderBegin ($title)
  {
    if ($this->debugMode)
      echo "\n<!-- ########[ SELENIA ]######## -- $title -->\n";
  }

  function debugHeaderEnd ($title)
  {
    if ($this->debugMode)
      echo "\n<!--/########[ SELENIA ]######## -- /$title -->\n";
  }

  protected function render ()
  {
    global $application;

    ob_start ();
    $this->renderChildren ();
    $pageContent = ob_get_clean () . $this->bodyContent;

    echo $this->doctype;
    $this->begin ("html");
    $this->attr ('class',
      enum (' ',
        when ($this->browserIsIE, 'IE'),
        when ($this->browserIsIE9, 'IE9'),
        when ($this->browserIsIE10, 'IE10'),
        when ($this->browserIsIE11, 'IE11'),
        when ($this->browserIsEdge, 'Edge'),
        when ($this->browserIsFF, 'FF'),
        when ($this->browserIsSafari, 'SAFARI'),
        when ($this->browserIsChrome, 'CHROME'),
        when ($this->clientIsWindows, 'WIN')
      )
    );
    $this->begin ('head');
    $this->beginContent ();

    $this->debugHeaderBegin ("AUTO-GENERATED");

    $this->tag ('meta', [
      'charset' => $this->charset,
    ]);
    $this->tag ('title', null, $this->title);
    $this->tag ('base', ['href' => "$application->baseURI/"]);

    if (!empty($this->description))
      $this->tag ('meta', [
        'name'    => 'description',
        'content' => $this->description,
      ]);
    if (!empty($this->keywords))
      $this->tag ('meta', [
        'name'    => 'keywords',
        'content' => $this->keywords,
      ]);
    if (!empty($this->author))
      $this->tag ('meta', [
        'name'    => 'author',
        'content' => $this->author,
      ]);
    if (!empty($application->favicon))
      $this->tag ('link', [
        'rel'  => 'shortcut icon',
        'href' => $application->favicon,
      ]);

    $this->debugHeaderEnd ("AUTO-GENERATED");

    $this->debugHeaderBegin ("APP STYLES");

    foreach ($this->stylesheets as $URI) {
      if (substr ($URI, 0, 4) != 'http') {
        if (substr ($URI, 0, 1) != '/')
          $URI = $application->toURI ($URI);
      }
      $this->tag ('link', [
        'rel'  => 'stylesheet',
        'type' => 'text/css',
        'href' => $URI,
      ]);
    }
    if (!empty($this->inlineCssStyles)) {
      $css = '';
      foreach ($this->inlineCssStyles as $item)
        if ($item instanceof Metadata)
          $css .= $item->getContent ();
        else $css .= $item;
      $this->tag ('style', null, $css);
    }

    $this->debugHeaderEnd ("APP STYLES");

    $this->debugHeaderBegin ("CUSTOM HEAD CONTENT");

    if (isset($this->extraHeadTags))
      $this->setContent ($this->extraHeadTags);

    $this->debugHeaderEnd ("CUSTOM HEAD CONTENT");

    $this->end ();

    $this->begin ('body', $this->bodyAttrs);
    $this->beginContent ();


    $this->debugHeaderBegin ("APP CONTENT");

    echo $this->preForm;
    $this->begin ('form', [
      'action'       => property ($this, 'targetURL', $_SERVER['REQUEST_URI']),
      'method'       => 'post',
      'enctype'      => $this->enableFileUpload ? 'multipart/form-data' : null,
      'autocomplete' => $this->formAutocomplete ? null : 'off',
      'onsubmit'     => 'return Form_onSubmit()',
    ]);
    $this->tag ('input', [
      'type'  => 'hidden',
      'name'  => '_action',
      'value' => property ($this, 'defaultAction'),
    ]);
    echo $pageContent;
    $this->end (); // form

    $this->debugHeaderEnd ("APP CONTENT");


    $this->debugHeaderBegin ("APP SCRIPTS");

    foreach ($this->root->scripts as $URI) {
      if (substr ($URI, 0, 4) != 'http') {
        if (substr ($URI, 0, 1) != '/')
          $URI = $application->toURI ($URI);
      }
      $this->tag ('script', [
        'src' => $URI,
      ]);
    }
    if (!empty($this->inlineScripts)) {
      $code = '';
      foreach ($this->inlineScripts as $item)
        if ($item instanceof Metadata) {
          $code .= $item->getContent ();
        }
        else $code .= $item;
      $this->tag ('script', [], $code
      );
    }
    if (!empty($this->inlineDeferredScripts)) {
      $code = '$(function(){';
      foreach ($this->inlineDeferredScripts as $item)
        if ($item instanceof Metadata) {
          $code .= $item->getContent ();
        }
        else $code .= $item;
      $code .= '})';
      $this->tag ('script',
        [
          'type' => 'text/javascript',
        ],
        $code
      );
    }

    $this->debugHeaderEnd ("APP SCRIPTS");

    echo $this->footer;
    $this->end (); // body
    $this->end (); // html
  }

}
