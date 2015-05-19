<?php
namespace Selene;

use Selene\Matisse\Context;

/**
 * Predefined pipes provided by the framework.
 */
class DefaultPipes
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

  /**
   * @param string $v
   * @return string
   */
  function datePart ($v)
  {
    return explode(' ', $v) [0];
  }

}
