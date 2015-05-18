<?php
namespace Selene;

use Selene\Matisse\Context;

class Pipes
{
  /**
   * @param string $v
   * @param Context $ctx
   * @return string
   */
  function fileURL ($v, $ctx)
  {
    global $application;
    return $application->getFileDownloadURI($v);
  }
}
