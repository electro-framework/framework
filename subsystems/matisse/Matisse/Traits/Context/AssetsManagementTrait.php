<?php
namespace Selenia\Matisse\Traits\Context;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Lib\AssetsContext;

/**
 * Manages external and embedded CSS stylesheets and javascripts.
 */
trait AssetsManagementTrait
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
    if ($css instanceof Component)
      $css = $css->getContent ();
    if (exists ($name))
      $this->assets->inlineCssStyles[$name] = $css;
    else if ($prepend)
      array_unshift ($this->assets->inlineCssStyles, $css);
    else $this->assets->inlineCssStyles[] = $css;
  }

  /**
   * Similar to addInlineScript(), but the script will only run on the document.ready event.
   *
   * @param string|Component $code    Javascript code without the script tags.
   * @param string           $name    An identifier for the script, to prevent duplication.
   *                                  When multiple scripts with the same name are added, only the last one is
   *                                  considered.
   * @param bool             $prepend If true, prepend to current list instead of appending.
   * @see addInlineScript
   */
  function addInlineDeferredScript ($code, $name = null, $prepend = false)
  {
    if ($code instanceof Component)
      $code = $code->getContent ();
    if (exists ($name))
      $this->assets->inlineDeferredScripts[$name] = $code;
    else if ($prepend)
      array_unshift ($this->assets->inlineDeferredScripts, $code);
    else $this->assets->inlineDeferredScripts[] = $code;
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
    if ($code instanceof Component)
      $code = $code->getContent ();
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
      $to->inlineDeferredScripts = array_merge ($from->inlineDeferredScripts, $to->inlineDeferredScripts);
      $to->inlineScripts         = array_merge ($from->inlineScripts, $to->inlineScripts);
      $unique                    = array_diff ($from->scripts, $to->scripts);
      $to->scripts               = array_merge ($unique, $to->scripts);
      $unique                    = array_diff ($from->stylesheets, $to->stylesheets);
      $to->stylesheets           = array_merge ($unique, $to->stylesheets);
    }
    else {
      array_mergeInto ($to->inlineCssStyles, $from->inlineCssStyles);
      array_mergeInto ($to->inlineDeferredScripts, $from->inlineDeferredScripts);
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
        echo $item;
      echo "</script>";
    }
    if (!empty($this->assets->inlineDeferredScripts)) {
      echo '<script>
$(function(){';
      foreach ($this->assets->inlineDeferredScripts as $item)
        echo $item;
      echo '})
</script>';
    }
  }

  function outputStyles ()
  {
    foreach ($this->assets->stylesheets as $URI)
      echo '<link rel="stylesheet" tpye="text/css" href="' . $URI . '">';
    if (!empty($this->assets->inlineCssStyles)) {
      echo "<style>";
      foreach ($this->assets->inlineCssStyles as $item)
        echo $item;
      echo "</style>";
    }
  }

}
