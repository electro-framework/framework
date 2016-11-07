<?php
namespace Electro\ConsoleApplication\Config;

/**
 * Configuration settings for the ConsoleApplication core subsystem.
 */
class ConsoleSettings
{
  private $taskClasses = [];

  /**
   * Returns all the registered task classes (read-only).
   *
   * @return array
   */
  function getTaskClasses ()
  {
    return $this->taskClasses;
  }

  /**
   * @param string $class Name of the module's class that implements the module's tasks.
   * @return $this
   */
  function registerTasksFromClass ($class)
  {
    $this->taskClasses[] = $class;
    return $this;
  }

}
