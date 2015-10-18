<?php
namespace Selenia\Tasks\Shared\Base;
use Robo\Task\Composer\Base;

class ComposerTask extends Base
{
  function action ($action)
  {
    $this->action = $action;

    return $this;
  }

  function setPathToComposer ($path)
  {
    $this->command = $path;
  }

  public function run ()
  {
    $command = $this->getCommand ();

    return $this->executeCommand ($command);
  }

}
