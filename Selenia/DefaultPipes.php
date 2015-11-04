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

  /**
   * The ordinal value of an iterator index.
   * @param int $v
   * @return int
   */
  function ord ($v)
  {
    return $v + 1;
  }

  /**
   * Alternating values for iterator indexes (0 or 1); allows for specific formatting of odd/even rows.
   * @param int $v
   * @return int
   */
  function alt ($v)
  {
    return $v % 2;
  }

  /**
   * Returns `true` if a number is odd.
   * @param int $v
   * @return boolean
   */
  function odd ($v)
  {
    return $v % 2 == 1;
  }

  /**
   * Returns `true` if a number is even.
   * @param int $v
   * @return boolean
   */
  function even ($v)
  {
    return $v % 2 == 0;
  }

}
