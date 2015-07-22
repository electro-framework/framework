<?php
namespace Selene\Tasks;

use Selene\Tasks\Base\ComposerTask;

class UninstallPackageTask extends ComposerTask
{
  /** @var string */
  protected $package;

  function __construct ($packageName)
  {
    parent::__construct ();
    $this->package = $packageName;
  }

  public function run ()
  {
    $this->printTaskInfo ("Uninstalling package $this->package");
    $this->action ('remove')->arg ($this->package);

    return parent::run ();

  }

}
