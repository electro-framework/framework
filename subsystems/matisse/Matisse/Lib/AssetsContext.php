<?php
namespace Selenia\Matisse\Lib;

class AssetsContext
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
  public $inlineScripts = [];
  /**
   * For a context that is not the main context, this indicates how it will be merged back to main.
   *
   * @var bool
   */
  public $prepend = false;
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
}
