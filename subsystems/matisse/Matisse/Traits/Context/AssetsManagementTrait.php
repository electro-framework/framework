<?php
namespace Selenia\Matisse\Traits\Context;

use Selenia\Matisse\Components\Base\Component;

/**
 * Manages external and embedded CSS stylesheets and javascripts.
 */
trait AssetsManagementTrait
{
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
      $this->inlineCssStyles[$name] = $css;
    else if ($prepend)
      array_unshift ($this->inlineCssStyles, $css);
    else $this->inlineCssStyles[] = $css;
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
      $this->inlineDeferredScripts[$name] = $code;
    else if ($prepend)
      array_unshift ($this->inlineDeferredScripts, $code);
    else $this->inlineDeferredScripts[] = $code;
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

  function outputScripts ()
  {
    foreach ($this->scripts as $URI)
      echo "<script src=\"$URI\"></script>";
    if (!empty($this->inlineScripts)) {
      echo "<script>";
      foreach ($this->inlineScripts as $item)
        echo $item;
      echo "</script>";
    }
    if (!empty($this->inlineDeferredScripts)) {
      echo '<script>
$(function(){';
      foreach ($this->inlineDeferredScripts as $item)
        echo $item;
      echo '})
</script>';
    }
  }

  function outputStyles ()
  {
    foreach ($this->stylesheets as $URI)
      echo '<link rel="stylesheet" tpye="text/css" href="' . $URI . '">';
    if (!empty($this->inlineCssStyles)) {
      echo "<style>";
      foreach ($this->inlineCssStyles as $item)
        echo $item;
      echo "</style>";
    }
  }

}
