<?php
namespace Selenia\Matisse\Components\Internal;

use Selenia\Interfaces\Views\ViewInterface;
use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Parser\Context;

class Page extends Component
{
  public $allowsChildren = true;
  public $author         = '';
  public $autoHTML       = false;
  /**
   * A map of block names => block contents.
   *
   * @var string[]
   */
  public $blocks = [];
  /**
   * Map of attributes to set on the body tag.
   *
   * @var array Map of string => mixed
   */
  public $bodyAttrs = null;
  /**
   * Content to be appended to the page content. It is usually set via the Body component.
   *
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
  public $debugMode   = false;
  public $defaultAction;
  public $description      = '';
  public $doctype          = '<!DOCTYPE HTML>';
  public $enableFileUpload = false;
  public $extraHeadTags    = '';
  public $footer           = '';
  public $formAutocomplete = false;
  /**
   * Array of strings (or Parameter objects with child content) containing inline css code.
   *
   * @var array
   */
  public $inlineCssStyles = [];
  /**
   * Array of strings (or Parameter objects with child content) containing inline javascripts.
   *
   * @var array
   */
  public $inlineDeferredScripts = [];
  /**
   * Array of strings (or Parameter objects with child content) containing inline javascripts.
   *
   * @var array
   */
  public $inlineScripts = [];
  public $keywords      = '';
  /** Content to be inserted before the form element. */
  public $preForm = '';
  public $requestURI;
  /**
   * Array of strings/Parameters containing URLs of scripts to be loaded during the page loading process.
   *
   * @var array
   */
  public $scripts = [];
  /**
   * Array of strings/Parameters containing URLs of CSS stylesheets to be loaded during the page loading process.
   *
   * @var array
   */
  public $stylesheets = [];
  public $targetURL;
  public $title;
  /**
   * Some components (ex. Include) require a View instance in order to load additional views.
   *
   * @var ViewInterface
   */
  private $view;

  function __construct (Context $context)
  {
    parent::__construct ($context);
    $this->page = $this;
    $this->setTagName ('Page');
    $this->checkBrowser ();
    $this->requestURI = $_SERVER['REQUEST_URI'];
  }

  /**
   * Adds an inline stylesheet to the HEAD section of the page.
   *
   * @param mixed  $css     Either a string or a Parameter.
   * @param string $name    An identifier for the stylesheet, to prevent duplication.
   *                        When multiple stylesheets with the same name are added, only the last one is considered.
   * @param bool   $prepend If true, prepend to current list instead of appending.
   */
  function addInlineCss ($css, $name = null, $prepend = false)
  {
    if ($css instanceof Component)
      $css->attachTo ($this);
    if (exists ($name))
      $this->inlineCssStyles[$name] = $css;
    else if ($prepend)
      array_unshift ($this->inlineCssStyles, $css);
    else $this->inlineCssStyles[] = $css;
  }

  /**
   * Similar to addInlineScript(), but the script will only run on the document.ready event.
   *
   * @param mixed  $code    Either a string or a Parameter.
   * @param string $name    An identifier for the script, to prevent duplication.
   *                        When multiple scripts with the same name are added, only the last one is considered.
   * @param bool   $prepend If true, prepend to current list instead of appending.
   * @see addInlineScript
   */
  function addInlineDeferredScript ($code, $name = null, $prepend = false)
  {
    if ($code instanceof Component)
      $code->attachTo ($this);
    if (exists ($name))
      $this->inlineDeferredScripts[$name] = $code;
    else if ($prepend)
      array_unshift ($this->inlineDeferredScripts, $code);
    else $this->inlineDeferredScripts[] = $code;
  }

  /**
   * Adds an inline script to the HEAD section of the page.
   *
   * @param mixed  $code    Either a string or a Parameter.
   * @param string $name    An identifier for the script, to prevent duplication.
   *                        When multiple scripts with the same name are added, only the last one is considered.
   * @param bool   $prepend If true, prepend to current list instead of appending.
   */
  function addInlineScript ($code, $name = null, $prepend = false)
  {
    if ($code instanceof Component)
      $code->attachTo ($this);
    if (exists ($name))
      $this->inlineScripts[$name] = $code;
    else if ($prepend)
      array_unshift ($this->inlineScripts, $code);
    else $this->inlineScripts[] = $code;
  }

  function addScript ($uri, $prepend = false)
  {
    if (array_search ($uri, $this->scripts) === false)
      if ($prepend)
        array_unshift ($this->scripts, $uri);
      else $this->scripts[] = $uri;
  }

  function addStylesheet ($uri, $prepend = false)
  {
    if (array_search ($uri, $this->stylesheets) === false)
      if ($prepend)
        array_unshift ($this->stylesheets, $uri);
      else $this->stylesheets[] = $uri;
  }

  /**
   * Appends content to a specific block.
   *
   * @param string $name An arbitrary block name.
   * @param string $content
   */
  function appendToBlock ($name, $content)
  {
    if (!isset($this->blocks[$name]))
      $this->blocks[$name] = $content;
    else $this->blocks[$name] .= $content;
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

  /**
   * Returns the content of a specific block.
   *
   * @param string $name An arbitrary block name.
   * @returns string
   */
  function getBlock ($name)
  {
    return get ($this->blocks, $name, '');
  }

  /**
   * The currently active view engine.
   *
   * @return ViewInterface
   * @throws ComponentException
   */
  function getView ()
  {
    if (isset($this->view))
      return $this->view;
    else throw new ComponentException($this, "A view instance has not been assigned to the Page");
  }

  /**
   * @param ViewInterface $view
   */
  function setView (ViewInterface $view)
  {
    $this->view = $view;
  }

  /**
   * Checks if a block with the specified name exists and has content.
   *
   * @param string $name An arbitrary block name.
   * @return bool
   */
  function hasBlock ($name)
  {
    return get ($this->blocks, $name, '') != '';
  }

  /**
   * Prepends content to a specific block.
   *
   * @param string $name An arbitrary block name.
   * @param string $content
   */
  function prependToBlock ($name, $content)
  {
    if (!isset($this->blocks[$name]))
      $this->blocks[$name] = $content;
    else $this->blocks[$name] = $content . $this->blocks[$name];
  }

  /**
   * Saves a string on a specific block, overriding the previous content of it.
   *
   * @param string $name An arbitrary block name.
   * @param string $content
   */
  function setBlock ($name, $content)
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

      foreach ($this->page->scripts as $URI) {
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
    else $this->renderChildren ();
  }

}
