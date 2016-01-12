<?php
namespace Selenia\Matisse\Traits\Context;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Macro\Macro;
use Selenia\Matisse\Exceptions\FileIOException;
use Selenia\Matisse\Exceptions\ParseException;
use Selenia\Matisse\Parser\Parser;

/**
 * Manages macros loading, storage and retrieval.
 */
trait MacrosManagementTrait
{
  /**
   * @var string[]
   */
  public $macrosDirectories = [];
  /**
   * File extension of macro files.
   *
   * @var string
   */
  public $macrosExt = '.html';
  /**
   * A list of memorized macros for the current request.
   *
   * @var Macro[]
   */
  private $macros = [];

  function addMacro ($name, Macro $macro)
  {
    if (isset($this->macros[$name]))
      throw new ParseException("Can't redefine the <kbd>$name</kbd> macro");
    $this->macros[$name] = $macro;
    // Remove macro from its original location. It now lives on only as a detached template.
    $macro->remove ();
  }

  /**
   * @param string $name
   * @return Macro
   */
  function getMacro ($name)
  {
    return get ($this->macros, $name);
  }

  /**
   * Searches for a file defining a macro for the given tag name.
   *
   * @param string    $tagName
   * @param Component $parent
   * @return Macro
   * @throws FileIOException
   * @throws ParseException
   */
  function loadMacro ($tagName, Component $parent)
  {
    $filename = normalizeTagName ($tagName) . $this->macrosExt;
    $content  = $this->loadMacroFile ($filename);
    $parser   = new Parser;
    $parser->parse ($content, $parent);
    $macro = $this->getMacro ($tagName);
    if (isset($macro))
      return $macro;
    throw new ParseException("File <b>$filename</b> does not define a macro named <b>$tagName</b>.");
  }

  private function loadMacroFile ($filename)
  {
    foreach ($this->macrosDirectories as $dir) {
      $f = loadFile ("$dir/$filename", false);
      if ($f) return $f;
    }
    throw new FileIOException($filename);
  }

}
