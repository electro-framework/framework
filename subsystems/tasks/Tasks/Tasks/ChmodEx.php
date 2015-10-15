<?php
namespace Selenia\Tasks;
use Robo\Common\TaskIO;
use Robo\Contract\TaskInterface;
use Robo\Exception\TaskException;
use Robo\Result;

class ChmodEx implements TaskInterface
{
  use TaskIO;

  // configuration params
  protected $dirs;
  protected $files;
  protected $path;
  protected $recursive = true;

  function __construct ($path)
  {
    $this->path = $path;
  }

  function dirs ($mod)
  {
    $this->dirs = $mod;

    return $this;
  }

  function files ($mod)
  {
    $this->files = $mod;

    return $this;

  }

  function recursive ($v = true)
  {
    $this->recursive = $v;

    return $this;
  }

  /**
   * @return \Robo\Result
   */
  function run ()
  {
    $this->walk ($this->path);
    $this->printTaskInfo ("Permissions applied on <info>$this->path</info>" .
                          ($this->recursive ? " and subfolders" : ''));

    return Result::success ($this);
  }

  protected function walk ($path)
  {
    if (is_dir ($path)) {
      chmod ($path, $this->dirs);
      if ($this->recursive) {
        $dir = @opendir ($path);
        if ($dir === false)
          throw new TaskException($this, "Cannot open source directory '$path'");
        while (false !== ($file = readdir ($dir))) {
          if (($file !== '.') && ($file !== '..'))
            $this->walk ("$path/$file");
        }
        closedir ($dir);
      }
    }
    else chmod ($path, $this->files);
  }

}
