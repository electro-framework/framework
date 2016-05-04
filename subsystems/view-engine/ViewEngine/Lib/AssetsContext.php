<?php
namespace Selenia\ViewEngine\Lib;

use Selenia\Matisse\Components\Base\Component;

class AssetsContext
{
  /**
   * Array of strings (or components with child content) containing inline css code.
   *
   * @var string[]|Component[]
   */
  public $inlineCssStyles = [];
  /**
   * Array of strings (or components with child content) containing inline javascripts.
   *
   * @var string[]|Component[]
   */
  public $inlineScripts = [];
  /**
   * For a context that is not the main context, this indicates how it will be merged back to main.
   *
   * @var bool
   */
  public $prepend = false;
  /**
   * Array of strings containing URLs of scripts to be loaded during the page loading process.
   *
   * @var string[]
   */
  public $scripts = [];
  /**
   * Array of strings containing URLs of CSS stylesheets to be loaded during the page loading process.
   *
   * @var string[]
   */
  public $stylesheets = [];
}
