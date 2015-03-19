<?php
namespace impactwave\matisse;


class Context
{
  /**
   * A list of data sources defined for the current request.
   * @var array
   */
  public $dataSources = [];
  /**
   * A list of memorized templates for the current request.
   * @var array
   */
  public $templates = [];
  /**
   * Set to true to generate pretty-printed markup.
   * @var bool
   */
  public $debugMode = false;
  /**
   * Remove white space around raw markup blocks.
   * @var bool
   */
  public $condenseLiterals = false;
  /**
   * @var string[]
   */
  public $templateDirectories = [];
}