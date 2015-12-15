<?php
namespace Selenia\Matisse\Components;
use Selenia\Matisse\Component;
use Selenia\Matisse\Context;

class Page extends Component
{
  public $allowsChildren = true;
  /**
   * Content to be prepended to the page content. It is usually set via the Body component.
   * @var string
   */
  public $bodyContent = '';

  /**
   * Array of strings/Parameters containing URLs of CSS stylesheets to be loaded during the page loading process.
   * @var array
   */
  public $stylesheets = [];

  /**
   * Array of strings/Parameters containing URLs of scripts to be loaded during the page loading process.
   * @var array
   */
  public $scripts = [];

  /**
   * Array of strings (or Parameter objects with child content) containing inline javascripts.
   * @var array
   */
  public $inlineScripts = [];

  /**
   * Array of strings (or Parameter objects with child content) containing inline javascripts.
   * @var array
   */
  public $inlineDeferredScripts = [];

  /**
   * Array of strings (or Parameter objects with child content) containing inline css code.
   * @var array
   */
  public $inlineCssStyles = [];

  public $autoHTML         = true;
  public $doctype          = '<!DOCTYPE HTML>';
  public $charset          = 'UTF-8';
  public $title;
  public $browserIsIE      = false;
  public $browserIsIE6     = false;
  public $browserIsIE7     = false;
  public $browserIsIE8     = false;
  public $browserIsIE9     = false;
  public $browserIsFF      = false;
  public $browserIsSafari  = false;
  public $browserIsChrome  = false;
  public $clientIsWindows  = false;
  public $requestURI;
  public $enableFileUpload = false;
  public $formAutocomplete = false;
  public $description      = '';
  public $keywords         = '';
  public $author           = '';
  public $footer           = '';
  public $extraHeadTags    = '';
  public $targetURL;
  public $defaultAction;
  /** Content to be inserted before the form element. */
  public $preForm = '';
  /**
   * Map of attributes to set on the body tag.
   * @var array Map of string => mixed
   */
  public $bodyAttrs = null;

  public function __construct (Context $context)
  {
    parent::__construct ($context);
    $this->page = $this;
    $this->setTagName ('Page');
    $this->checkBrowser ();
    $this->requestURI = $_SERVER['REQUEST_URI'];
  }

  public function checkBrowser ()
  {
    $b = get ($_SERVER, 'HTTP_USER_AGENT', '');
    if (preg_match ('#MSIE (\d+)#', $b, $match)) {
      $v                  = intval ($match[1]);
      $this->browserIsIE6 = $v == 6;
      $this->browserIsIE7 = $v == 7;
      $this->browserIsIE8 = $v == 8;
      $this->browserIsIE9 = $v >= 9;
      $this->browserIsIE  = true;
    }
    $this->browserIsFF     = strpos ($b, 'Gecko/') !== false;
    $this->browserIsSafari = strpos ($b, 'Safari') !== false && strpos ($b, 'Chrome') === false;
    $this->browserIsChrome = strpos ($b, 'Chrome') !== false;
    $this->clientIsWindows = strpos ($b, 'Windows') !== false;
  }

  public function addStylesheet ($uri, $prepend = false)
  {
    if (array_search ($uri, $this->stylesheets) === false)
      if ($prepend)
        array_unshift ($this->stylesheets, $uri);
      else $this->stylesheets[] = $uri;
  }

  public function addScript ($uri, $prepend = false)
  {
    if (array_search ($uri, $this->scripts) === false)
      if ($prepend)
        array_unshift ($this->scripts, $uri);
      else $this->scripts[] = $uri;
  }

  /**
   * Adds an inline script to the HEAD section of the page.
   * @param mixed  $code    Either a string or a Parameter.
   * @param string $name    An identifier for the script, to prevent duplication.
   *                        When multiple scripts with the same name are added, only the last one is considered.
   * @param bool   $prepend If true, prepend to current list instead of appending.
   */
  public function addInlineScript ($code, $name = null, $prepend = false)
  {
    if ($code instanceof Component)
      $code->attachTo ($this);
    if (isset($name))
      $this->inlineScripts[$name] = $code;
    else if ($prepend)
      array_unshift ($this->inlineScripts, $code);
    else $this->inlineScripts[] = $code;
  }

  /**
   * Similar to addInlineScript(), but the script will only run on the document.ready event.
   * @param mixed  $code    Either a string or a Parameter.
   * @param string $name    An identifier for the script, to prevent duplication.
   *                        When multiple scripts with the same name are added, only the last one is considered.
   * @param bool   $prepend If true, prepend to current list instead of appending.
   * @see addInlineScript
   */
  public function addInlineDeferredScript ($code, $name = null, $prepend = false)
  {
    if ($code instanceof Component)
      $code->attachTo ($this);
    if (isset($name))
      $this->inlineDeferredScripts[$name] = $code;
    else if ($prepend)
      array_unshift ($this->inlineDeferredScripts, $code);
    else $this->inlineDeferredScripts[] = $code;
  }

  /**
   * Adds an inline stylesheet to the HEAD section of the page.
   * @param mixed  $css     Either a string or a Parameter.
   * @param string $name    An identifier for the stylesheet, to prevent duplication.
   *                        When multiple stylesheets with the same name are added, only the last one is considered.
   * @param bool   $prepend If true, prepend to current list instead of appending.
   */
  public function addInlineCss ($css, $name = null, $prepend = false)
  {
    if ($css instanceof Component)
      $css->attachTo ($this);
    if (isset($name))
      $this->inlineCssStyles[$name] = $css;
    else if ($prepend)
      array_unshift ($this->inlineCssStyles, $css);
    else $this->inlineCssStyles[] = $css;
  }

  protected function render ()
  {
    global $application;
    if ($this->autoHTML) {

      ob_start ();
      $this->renderChildren ();
      $pageContent = $this->bodyContent . ob_get_clean ();

      echo $this->doctype;
      $this->beginTag ("html");
      $this->addAttribute ('class',
        enum (' ',
          when ($this->browserIsIE, 'IE'),
          when ($this->browserIsIE6, 'IE6'),
          when ($this->browserIsIE7, 'IE7'),
          when ($this->browserIsIE8, 'IE8'),
          when ($this->browserIsIE9, 'IE9'),
          when ($this->browserIsFF, 'FF'),
          when ($this->browserIsSafari, 'SAFARI'),
          when ($this->browserIsChrome, 'CHROME'),
          when ($this->clientIsWindows, 'WIN')
        )
      );
      $this->beginTag ('head');
      $this->addTag ('meta', [
        'charset' => $this->charset,
      ]);
      $this->addTag ('title', null, $this->title);
      $this->addTag ('base', ['href' => "$application->baseURI/"]);

      foreach ($this->stylesheets as $URI) {
        if (substr ($URI, 0, 4) != 'http') {
          if (substr ($URI, 0, 1) != '/')
            $URI = $application->toURI ($URI);
        }
        $this->addTag ('link', [
          'rel'  => 'stylesheet',
          'type' => 'text/css',
          'href' => $URI,
        ]);
      }
      if (!empty($this->inlineCssStyles)) {
        $css = '';
        foreach ($this->inlineCssStyles as $item)
          if ($item instanceof Parameter)
            $css .= $item->getContent ();
          else $css .= $item;
        $this->addTag ('style', null, $css);
      }

      if (!empty($this->description))
        $this->addTag ('meta', [
          'name'    => 'description',
          'content' => $this->description,
        ]);
      if (!empty($this->keywords))
        $this->addTag ('meta', [
          'name'    => 'keywords',
          'content' => $this->keywords,
        ]);
      if (!empty($this->author))
        $this->addTag ('meta', [
          'name'    => 'author',
          'content' => $this->author,
        ]);
      if (!empty($application->favicon))
        $this->addTag ('link', [
          'rel'  => 'shortcut icon',
          'href' => $application->favicon,
        ]);
      if (isset($this->extraHeadTags))
        $this->setContent ($this->extraHeadTags);
      $this->endTag ();
      $this->beginTag ('body', $this->bodyAttrs);
      $this->setContent ($this->preForm);
      $this->beginTag ('form', [
        'action'       => property ($this, 'targetURL', $_SERVER['REQUEST_URI']),
        'method'       => 'post',
        'enctype'      => $this->enableFileUpload ? 'multipart/form-data' : null,
        'autocomplete' => $this->formAutocomplete ? null : 'off',
        'onsubmit'     => 'return Form_onSubmit()',
      ]);
      $this->addTag ('input', [
        'type'  => 'hidden',
        'name'  => '_action',
        'value' => property ($this, 'defaultAction'),
      ]);

      echo $pageContent;

      $this->endTag (); // form

      foreach ($this->page->scripts as $URI) {
        if (substr ($URI, 0, 4) != 'http') {
          if (substr ($URI, 0, 1) != '/')
            $URI = $application->toURI ($URI);
        }
        $this->addTag ('script', [
          'type' => 'text/javascript',
          'src'  => $URI,
        ]);
      }
      if (!empty($this->inlineScripts)) {
        $code = '';
        foreach ($this->inlineScripts as $item)
          if ($item instanceof Parameter) {
            $code .= $item->getContent ();
          }
          else $code .= $item;
        $this->addTag ('script',
          [
            'type' => 'text/javascript',
          ],
          $code
        );
      }
      if (!empty($this->inlineDeferredScripts)) {
        $code = '$(function(){';
        foreach ($this->inlineDeferredScripts as $item)
          if ($item instanceof Parameter) {
            $code .= $item->getContent ();
          }
          else $code .= $item;
        $code .= '})';
        $this->addTag ('script',
          [
            'type' => 'text/javascript',
          ],
          $code
        );
      }

      echo $this->footer;
      $this->endTag (); // body
      $this->endTag (); // html
    }
    else $this->renderChildren ();
  }

}
