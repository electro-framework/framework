<?php
namespace Selene\Routing;

class Page extends Route {

  /**
   *
   * @var string
   */
  public $title;
  /**
   *
   * @var string
   */
  public $model;
  /**
   *
   * @var string
   */
  public $view;
  /**
   *
   * @var array
   */
  public $viewModel;
  /**
   *
   * @var string
   */
  public $controller;
  /**
   *
   * @var boolean
   */
  public $onMenu = false;
  /**
   *
   * @var string
   */
  public $icon;
  /**
   * @var
   */
  public $isIndex = false;
  /**
   * @var array
   */
  public $links;
  /**
   *
   * @var boolean
   */
  public $autoController = false;
  /**
   *
   * @var array
   */
  public $preset;

}