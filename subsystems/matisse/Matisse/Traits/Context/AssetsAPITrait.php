<?php
namespace Selenia\Matisse\Traits\Context;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Lib\AssetsContext;

/**
 * Manages external and embedded CSS stylesheets and javascripts.
 */
trait AssetsAPITrait
{
  /**
   * @var AssetsContext
   */
  public $assets;
  /**
   * @var AssetsContext
   */
  public $mainAssets;

  /**
   * Adds an inline stylesheet to the HEAD section of the page.
   *
   * @param string|Component $css     CSS source code without style tags.
   * @param string           $name    An identifier for the stylesheet, to prevent duplication.
   *                                  When multiple stylesheets with the same name are added, only the last one is
   *                                  considered.
   * @param bool             $prepend If true, prepend to current list instead of appending.
   */
  function addInlineCss ($css, $name = null, $prepend = false)
  {
    if (exists ($name))
      $this->assets->inlineCssStyles[$name] = $css;
    else if ($prepend)
      array_unshift ($this->assets->inlineCssStyles, $css);
    else $this->assets->inlineCssStyles[] = $css;
  }

  /**
   * Adds an inline script to the HEAD section of the page.
   *
   * @param string|Component $code    Javascript code without the script tags.
   * @param string           $name    An identifier for the script, to prevent duplication.
   *                                  When multiple scripts with the same name are added, only the last one is
   *                                  considered.
   * @param bool             $prepend If true, prepend to current list instead of appending.
   */
  function addInlineScript ($code, $name = null, $prepend = false)
  {
    if (exists ($name))
      $this->assets->inlineScripts[$name] = $code;
    else if ($prepend)
      array_unshift ($this->assets->inlineScripts, $code);
    else $this->assets->inlineScripts[] = $code;
  }

  function addScript ($uri, $prepend = false)
  {
    if (array_search ($uri, $this->assets->scripts) === false)
      if ($prepend)
        array_unshift ($this->assets->scripts, $uri);
      else $this->assets->scripts[] = $uri;
  }

  function addStylesheet ($uri, $prepend = false)
  {
    if (array_search ($uri, $this->assets->stylesheets) === false)
      if ($prepend)
        array_unshift ($this->assets->stylesheets, $uri);
      else $this->assets->stylesheets[] = $uri;
  }

  function beginAssetsContext ($prepend = false)
  {
    $this->assets          = new AssetsContext;
    $this->assets->prepend = $prepend;
  }

  function endAssetsContext ()
  {
    $from = $this->assets;
    $to   = $this->mainAssets;

    if ($from->prepend) {
      $to->inlineCssStyles       = array_merge ($from->inlineCssStyles, $to->inlineCssStyles);
      $to->inlineScripts         = array_merge ($from->inlineScripts, $to->inlineScripts);
      $unique                    = array_diff ($from->scripts, $to->scripts);
      $to->scripts               = array_merge ($unique, $to->scripts);
      $unique                    = array_diff ($from->stylesheets, $to->stylesheets);
      $to->stylesheets           = array_merge ($unique, $to->stylesheets);
    }
    else {
      array_mergeInto ($to->inlineCssStyles, $from->inlineCssStyles);
      array_mergeInto ($to->inlineScripts, $from->inlineScripts);
      $unique = array_diff ($from->scripts, $to->scripts);
      array_mergeInto ($to->scripts, $unique);
      $unique = array_diff ($from->stylesheets, $to->stylesheets);
      array_mergeInto ($to->stylesheets, $unique);
    }

    $this->assets = $to;
  }

  function outputScripts ()
  {
    foreach ($this->assets->scripts as $URI)
      echo "<script src=\"$URI\"></script>";
    if (!empty($this->assets->inlineScripts)) {
      echo "<script>";
      foreach ($this->assets->inlineScripts as $item)
        echo is_string($item) ? $item : $item->getContent ();
      echo "</script>";
    }
  }

  function outputStyles ()
  {
    foreach ($this->assets->stylesheets as $URI)
      echo '<link rel="stylesheet" tpye="text/css" href="' . $URI . '">';
    if (!empty($this->assets->inlineCssStyles)) {
      echo "<style>";
      foreach ($this->assets->inlineCssStyles as $item)
        echo is_string($item) ? $item : $item->getContent ();
      echo "</style>";
    }
  }

}
