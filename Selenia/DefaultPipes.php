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
   * Alternating values for iterator indexes (0 or 1); allows for specific formatting of odd/even rows.
   *
   * @param int $v
   * @return int
   */
  function alt ($v)
  {
    return $v % 2;
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
   * Returns `true` if a number is even.
   *
   * @param int $v
   * @return boolean
   */
  function even ($v)
  {
    return $v % 2 == 0;
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
   * Converts line breaks to `<br>` tags.
   *
   * @param $v
   * @return string
   */
  function nl2br ($v)
  {
    return nl2br ($v);
  }

  /**
   * Returns `true` if a number is odd.
   *
   * @param int $v
   * @return boolean
   */
  function odd ($v)
  {
    return $v % 2 == 1;
  }

  /**
   * The ordinal value of an iterator index.
   *
   * @param int $v
   * @return int
   */
  function ord ($v)
  {
    return $v + 1;
  }

  /**
   * @param mixed  $v
   * @param string $true
   * @param string $false
   * @return string
   */
  function then ($v, $true = '', $false = '')
  {
    return $v ? $true : $false;
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
