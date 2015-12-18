<?php
namespace Selenia\Matisse\Components;
use Selenia\Matisse\Component;
use Selenia\Matisse\Context;

class Page extends Component
{
  public $allowsChildren = true;
  public $author         = '';
  public $autoHTML       = true;
  /**
   * A map of block names => block contents (array of Component).
   * @var Component[][]
   */
  public $blocks = [];
  /**
   * Map of attributes to set on the body tag.
   * @var array Map of string => mixed
   */
  public $bodyAttrs = null;
  /**
   * Content to be appended to the page content. It is usually set via the Body component.
   * @var string
   */
  public $bodyContent      = '';
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
  public $defaultAction;
  public $description      = '';
  public $doctype          = '<!DOCTYPE HTML>';
  public $enableFileUpload = false;
  public $extraHeadTags    = '';
  public $footer           = '';
  public $formAutocomplete = false;
  /**
   * Array of strings (or Parameter objects with child content) containing inline css code.
   * @var array
   */
  public $inlineCssStyles = [];
  /**
   * Array of strings (or Parameter objects with child content) containing inline javascripts.
   * @var array
   */
  public $inlineDeferredScripts = [];
  /**
   * Array of strings (or Parameter objects with child content) containing inline javascripts.
   * @var array
   */
  public $inlineScripts = [];
  public $keywords      = '';
  /** Content to be inserted before the form element. */
  public $preForm = '';
  public $requestURI;
  /**
   * Array of strings/Parameters containing URLs of scripts to be loaded during the page loading process.
   * @var array
   */
  public $scripts = [];
  /**
   * Array of strings/Parameters containing URLs of CSS stylesheets to be loaded during the page loading process.
   * @var array
   */
  public $stylesheets = [];
  public $targetURL;
  public $title;

  public function __construct (Context $context)
  {
    parent::__construct ($context);
    $this->page = $this;
    $this->setTagName ('Page');
    $this->checkBrowser ();
    $this->requestURI = $_SERVER['REQUEST_URI'];
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

  public function addScript ($uri, $prepend = false)
  {
    if (array_search ($uri, $this->scripts) === false)
      if ($prepend)
        array_unshift ($this->scripts, $uri);
      else $this->scripts[] = $uri;
  }

  public function addStylesheet ($uri, $prepend = false)
  {
    if (array_search ($uri, $this->stylesheets) === false)
      if ($prepend)
        array_unshift ($this->stylesheets, $uri);
      else $this->stylesheets[] = $uri;
  }

  /**
   * Appends an array of components to a specific block.
   * @param string      $name    An arbitrary block name.
   * @param Component[] $content An array of <b>detached</b> components.
   */
  public function appendToBlock ($name, array $content)
  {
    if (!isset($this->blocks[$name]))
      $this->blocks[$name] = $content;
    else $this->blocks[$name] = array_merge ($this->blocks[$name], $content);
  }

  public function checkBrowser ()
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

  /**
   * Returns the content of a specific block.
   * @param string $name An arbitrary block name.
   * @returns Component[] $content An array of <b>detached</b> components.
   */
  public function getBlock ($name)
  {
    return get ($this->blocks, $name, []);
  }

  /**
   * Saves an array of components on a specific block, overriding the previous content of it.
   * @param string      $name    An arbitrary block name.
   * @param Component[] $content An array of <b>detached</b> components.
   */
  public function setBlock ($name, array $content)
  {
    $this->blocks[$name] = $content;
  }

  protected function render ()
  {
    global $application;
    if ($this->autoHTML) {

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
      $this->tag ('meta', [
        'charset' => $this->charset,
      ]);
      $this->tag ('title', null, $this->title);
      $this->tag ('base', ['href' => "$application->baseURI/"]);

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
          if ($item instanceof Parameter)
            $css .= $item->getContent ();
          else $css .= $item;
        $this->tag ('style', null, $css);
      }

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
      if (isset($this->extraHeadTags))
        $this->setContent ($this->extraHeadTags);
      $this->end ();
      $this->begin ('body', $this->bodyAttrs);
      $this->setContent ($this->preForm);
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

      foreach ($this->page->scripts as $URI) {
        if (substr ($URI, 0, 4) != 'http') {
          if (substr ($URI, 0, 1) != '/')
            $URI = $application->toURI ($URI);
        }
        $this->tag ('script', [
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
        $this->tag ('script',
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
        $this->tag ('script',
          [
            'type' => 'text/javascript',
          ],
          $code
        );
      }

      echo $this->footer;
      $this->end (); // body
      $this->end (); // html
    }
    else $this->renderChildren ();
  }

}
