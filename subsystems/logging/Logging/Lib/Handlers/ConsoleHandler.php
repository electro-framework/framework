<?php

namespace Electro\Logging\Lib\Handlers;

use Electro\Logging\Config\LogSettings;
use InvalidArgumentException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A log handler for a Symfony Console output target.
 *
 * <p>**Note:** when running a console application, use the `-v, -vv and -vvv` command-line options to choose the log
 * levels that will by displayed.
 */
class ConsoleHandler extends AbstractProcessingHandler
{
  /** @var int[] */
  protected $verbosityLevelMap = [
    LogLevel::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
    LogLevel::ALERT     => OutputInterface::VERBOSITY_NORMAL,
    LogLevel::CRITICAL  => OutputInterface::VERBOSITY_NORMAL,
    LogLevel::ERROR     => OutputInterface::VERBOSITY_NORMAL,
    LogLevel::WARNING   => OutputInterface::VERBOSITY_VERBOSE,
    LogLevel::NOTICE    => OutputInterface::VERBOSITY_VERY_VERBOSE,
    LogLevel::INFO      => OutputInterface::VERBOSITY_VERY_VERBOSE,
    LogLevel::DEBUG     => OutputInterface::VERBOSITY_DEBUG,
  ];
  /** @var OutputInterface */
  private $output;

  public function __construct (OutputInterface $output, LogSettings $logSettings, $bubble = true)
  {
    if ($output instanceof ConsoleOutputInterface)
      $output = $output->getErrorOutput ();
    $this->output            = $output;
    $this->verbosityLevelMap = $logSettings->verbosityLevelMap + $this->verbosityLevelMap;
    parent::__construct (Logger::DEBUG, $bubble);
  }

  public function isHandling (array $record)
  {
    $level = $record['level'];
    if (!isset($this->verbosityLevelMap[$level]))
      throw new InvalidArgumentException(sprintf ('Log level "%s" is not supported.', $level));
    return $this->output->getVerbosity () >= $this->verbosityLevelMap[$level];
  }

  /**
   * @param array $verbosityLevelMap
   * @return $this
   */
  function setVerbosityLevelMap (array $verbosityLevelMap = [])
  {
    $this->verbosityLevelMap = $verbosityLevelMap + $this->verbosityLevelMap;
    return $this;
  }

  protected function write (array $record)
  {
    $this->output->writeln ($record['formatted']);
  }

}
