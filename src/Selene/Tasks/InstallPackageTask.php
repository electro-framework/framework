<?php
namespace Selene\Tasks;

use Selene\Tasks\Base\ComposerTask;

class InstallPackageTask extends ComposerTask
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
    $this->printTaskInfo ("Installing package $this->package");
    $this->action ('require')->arg ($this->package);

    return parent::run ();

  }

}
