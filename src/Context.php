<?php
namespace Selene\Matisse;
use Selene\Matisse\Components\Template;
use Selene\Matisse\Exceptions\FileIOException;

class Context
{
  /**
   * A list of data sources defined for the current request.
   * @var array
   */
  public $dataSources = [];
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

  /**
   * A map of tag names to fully qualified PHP class names.
   * @var array string => string
   */
  private $tags = [];
  /**
   * A list of memorized templates for the current request.
   * @var array
   */
  private $templates = [];

  /**
   * @param array $tags A map of tag names to fully qualified PHP class names.
   */
  public function __construct (array &$tags)
  {
    $this->tags =& $tags;
  }

  public function getClassForTag ($tag)
  {
    return get ($this->tags, $tag);
  }

  public function addTemplate ($name, Template $template)
  {
    $this->templates[$name] = $template;
  }

  public function getTemplate ($name)
  {
    return get ($this->templates, $name);
  }

  public function loadTemplate ($filename)
  {
    foreach ($this->templateDirectories as $dir) {
      $f = loadFile ("$dir/$filename", false);
      if ($f) return $f;
    }
    throw new FileIOException($filename);
  }
}