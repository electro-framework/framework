<?php
namespace Electro\ViewEngine\Lib;

use Electro\Exceptions\FatalException;
use Electro\Interfaces\EventEmitterInterface;
use Electro\Interfaces\Views\ViewEngineInterface;
use Electro\Interfaces\Views\ViewInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
use Electro\Interop\ViewModel;

class View implements ViewInterface
{
  /**
   * @var mixed
   */
  private $compiled = null;
  /**
   * @var EventEmitterInterface
   */
  private $emitter;
  /**
   * @var string
   */
  private $source = '';
  /**
   * @var string
   */
  private $templatePath;
  /**
   * @var ViewEngineInterface
   */
  private $viewEngine;

  /**
   * View constructor.
   *
   * @param ViewEngineInterface   $viewEngine
   * @param string|null           $templatePath
   * @param EventEmitterInterface $emitter
   */
  public function __construct (ViewEngineInterface $viewEngine, $templatePath, EventEmitterInterface $emitter)
  {
    $this->viewEngine = $viewEngine;
    $this->templatePath = $templatePath;
    $this->emitter = $emitter;
  }

  function compile ()
  {
    if (is_null ($this->source))
      throw new FatalException ("No template is set for compilation");
    $this->compiled = $this->viewEngine->compile ($this->source);
    return $this;
  }

  function getCompiled ()
  {
    return $this->compiled;
  }

  function setCompiled ($compiled)
  {
    $this->compiled = $compiled;
    return $this;
  }

  function getEngine ()
  {
    return $this->viewEngine;
  }

  function getSource ()
  {
    return $this->source;
  }

  function getTemplatePath ()
  {
    return $this->templatePath;
  }

  function setSource ($src)
  {
    $this->source   = $src;
    $this->compiled = null;
    return $this;
  }

  function render (ViewModel $data = null)
  {
    if (!$this->compiled)
      $this->compile ();
    $template = $this->compiled ?: $this->source;
    if (is_null ($template))
      throw new FatalException ("No template is set for rendering");
    if (is_null($data))
      $data = new ViewModel;
    $this->emitter->emit(ViewServiceInterface::EVENT_RENDER, $this, $data);
    return $this->viewEngine->render ($template, $data);
  }

}
