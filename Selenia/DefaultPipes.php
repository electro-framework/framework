<?php
namespace Selenia;

/**
 * Predefined pipes provided by the framework.
 */
class DefaultPipes
{
  private $app;

  function __construct (Application $app)
  {
    $this->app = $app;
  }

  /**
   * @param string $v
   * @return string
   */
  function currency ($v)
  {
    return formatMoney ($v) . ' €';
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
  function fileURL ($v)
  {
    return $this->app->getFileDownloadURI ($v);
  }

  /**
   * @param mixed $v
   * @return string
   */
  function json ($v)
  {
    return json_encode ($v, JSON_PRETTY_PRINT);
  }

  /**
   * @param string $v
   * @return string
   */
  function timePart ($v)
  {
    return explode (' ', $v) [1];
  }

}
