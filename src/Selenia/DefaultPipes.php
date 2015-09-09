<?php
namespace Selenia;

/**
 * Predefined pipes provided by the framework.
 */
class DefaultPipes
{
  /**
   * @param string $v
   * @return string
   */
  function fileURL ($v)
  {
    global $application;
    return $application->getFileDownloadURI ($v);
  }

  /**
   * @param string $v
   * @return string
   */
  function datePart ($v)
  {
    return explode (' ', $v) [0];
  }

  /**
   * @param string $v
   * @return string
   */
  function currency ($v)
  {
    return formatMoney ($v) . ' €';
  }

}
