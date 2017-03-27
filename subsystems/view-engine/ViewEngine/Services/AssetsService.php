<?php
namespace Electro\ViewEngine\Services;

use Electro\Interfaces\RenderableInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Traits\InspectionTrait;
use Electro\ViewEngine\Lib\AssetsContext;

/**
 * Manages external and embedded CSS stylesheets and javascripts.
 */
class AssetsService
{
  use InspectionTrait;

  static $INSPECTABLE = ['assets'];

  /**
   * @var AssetsContext
   */
  private $assets;
  /**
   * @var KernelSettings
   */
  private $kernelSettings;
  /**
   * @var AssetsContext
   */
  private $mainAssets;

  public function __construct (KernelSettings $kernelSettings)
  {
    $this->assets         = $this->mainAssets = new AssetsContext;
    $this->kernelSettings = $kernelSettings;
  }

  /**
   * Adds an inline stylesheet to the HEAD section of the page.
   *
   * @param string|RenderableInterface $css     CSS source code without style tags.
   * @param string                     $name    An identifier for the stylesheet, to prevent duplication.
   *                                            When multiple stylesheets with the same name are added, only the last
   *                                            one is considered.
   * @param bool                       $prepend If true, prepend to current list instead of appending.
   * @return $this
   */
  function addInlineCss ($css, $name = null, $prepend = false)
  {
    if (exists ($name))
      $this->assets->inlineCssStyles[$name] = $css;
    else if ($prepend)
      array_unshift ($this->assets->inlineCssStyles, $css);
    else $this->assets->inlineCssStyles[] = $css;
    return $this;
  }

  /**
   * Adds an inline script to the HEAD section of the page.
   *
   * @param string|RenderableInterface $code    Javascript code without the script tags.
   * @param string                     $name    An identifier for the script, to prevent duplication.
   *                                            When multiple scripts with the same name are added, only the last one is
   *                                            considered.
   * @param bool                       $prepend If true, prepend to current list instead of appending.
   * @return $this
   */
  function addInlineScript ($code, $name = null, $prepend = false)
  {
    if (exists ($name))
      $this->assets->inlineScripts[$name] = $code;
    else if ($prepend)
      array_unshift ($this->assets->inlineScripts, $code);
    else $this->assets->inlineScripts[] = $code;
    return $this;
  }

  /**
   * @param string $uri
   * @param bool   $prepend
   * @return $this
   */
  function addScript ($uri, $prepend = false)
  {
    if (array_search ($uri, $this->assets->scripts) === false) {
      if ($prepend)
        array_unshift ($this->assets->scripts, $uri);
      else $this->assets->scripts[] = $uri;
    }
    return $this;
  }

  /**
   * @param string $uri
   * @param bool   $prepend
   * @return $this
   */
  function addStylesheet ($uri, $prepend = false)
  {
    if (array_search ($uri, $this->assets->stylesheets) === false)
      if ($prepend)
        array_unshift ($this->assets->stylesheets, $uri);
      else $this->assets->stylesheets[] = $uri;
    return $this;
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
      $to->inlineCssStyles = array_merge ($from->inlineCssStyles, $to->inlineCssStyles);
      $to->inlineScripts   = array_merge ($from->inlineScripts, $to->inlineScripts);
      $unique              = array_diff ($from->scripts, $to->scripts);
      $to->scripts         = array_merge ($unique, $to->scripts);
      $unique              = array_diff ($from->stylesheets, $to->stylesheets);
      $to->stylesheets     = array_merge ($unique, $to->stylesheets);
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
      foreach ($this->assets->inlineScripts as $item) {
        $code = trim ((string)$item);
        $last = substr ($code, -1);
        echo $last != ';' && $last != '}' ? $code . ';' : $code . "\n";
      }
      echo "</script>";
    }
  }

  function outputStyles ()
  {
    foreach ($this->assets->stylesheets as $URI)
      echo '<link rel="stylesheet" type="text/css" href="' . $URI . '">';
    if (!empty($this->assets->inlineCssStyles)) {
      echo "<style>";
      foreach ($this->assets->inlineCssStyles as $item)
        echo $item;
      echo "</style>";
    }
  }

  /**
   * A list of relative file paths of assets published by a module, relative to that module's public folder.
   *
   * <p>Registered assets will be automatically loaded by rendered pages when they use the {@see outputScripts} or
   * {@see outputStyles} methods.
   * <p>Also, if they are located on a sub-directory of `/resources` , the framework's build process may automatically
   * concatenate and minify them for a release-grade build.
   *
   * @param string   $moduleName
   * @param string[] $assets
   * @return $this
   */
  function registerAssets ($moduleName, $assets)
  {
    $publicUrl = "{$this->kernelSettings->modulesPublishingPath}/$moduleName";
    // TODO: handle assets on a sub-directory of resources.
    foreach ($assets as $path) {
      $path = "$publicUrl/$path";
      $p    = strrpos ($path, '.');
      if (!$p) continue;
      $ext = substr ($path, $p + 1);
      switch ($ext) {
        case 'css':
          $this->addStylesheet ($path);
          break;
        case 'js':
          $this->addScript ($path);
          break;
      }
    }
    return $this;
  }

}
