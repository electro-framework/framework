<?php
namespace Selenia\Matisse\Lib;

use Selenia\Matisse\Components\Base\Component;

/**
 * A block of renderable content.
 *
 * <p>A block is, in reality, a list of content sets. Each set has an associated view model.
 * <p>A content set is a mixed DOM node / string list.
 *
 * <p>Blocks are lazily rendered on demand.
 * <p>When a block is rendered, each one of its content sets is rendered sequentially, bound to the associated view
 * model.
 */
class Block
{
  /**
   * @var array Type (Component[]|string)[]
   */
  private $contents = [];
  /**
   * @var DataBinder
   */
  private $dataBinder;
  /**
   * @var array Type (object|array)[]
   */
  private $viewModels = [];

  public function __construct (DataBinder $dataBinder)
  {
    $this->dataBinder = $dataBinder;
  }

  /**
   * @param string|Component[] $content
   * @return Component[]
   */
  static private function checkContent ($content)
  {
    if (!is_array ($content) && !is_string ($content))
      throw new \InvalidArgumentException(sprintf ("Block content must be <kbd>string|Component[]</kbd>, %s given",
        typeInfoOf ($content)));
    return $content;
  }

  function __debugInfo ()
  {
    return [
      'Content sets<sup>*</sup>' => count ($this->contents),
      'Contents<sup>*</sup>'     => $this->render (),
    ];
  }

  /**
   * Appends content to the block, and also saves the given view model.
   *
   * @param string|Component[] $content
   */
  function append ($content)
  {
    $this->contents[]   = self::checkContent ($content);
    $this->viewModels[] = $this->dataBinder->getViewModel ();
  }

  /**
   * Prepends content to the block, and also saves the given view model.
   *
   * @param string|Component[] $content
   */
  function prepend ($content)
  {
    array_unshift ($this->contents, self::checkContent ($content));
    array_unshift ($this->viewModels, $this->dataBinder->getViewModel ());
  }

  /**
   * Renders the block contents.
   *
   * @return string
   */
  public function render ()
  {
    $out = '';
    for ($i = 0, $c = count ($this->contents); $i < $c; ++$i) {
      $this->dataBinder->push ($this->viewModels[$i]);
      $content = $this->contents[$i];
      $out .= is_string ($content) ? $content : Component::getRenderingOfSet ($content);
      $this->dataBinder->pop ();
    }
    return $out;
  }

  /**
   * Sets the block's content.
   *
   * @param string|Component[] $content
   */
  public function set ($content)
  {
    $this->contents   = [self::checkContent ($content)];
    $this->viewModels = [$this->dataBinder->getViewModel ()];
    $this->components = [$this->dataBinder->getComponent ()];
  }
}
