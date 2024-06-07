<?php
namespace Electro\Logging\Services;

use Electro\Interfaces\Logging\LogCentralInterface;
use Electro\Interop\Logging\Formatters;
use Electro\Interop\Logging\Handlers;
use Electro\Interop\Logging\Loggers;
use Electro\Interop\Logging\Processors;
use Electro\Logging\Lib\LogHandlerRef;
use Monolog\Handler\GroupHandler;
use PhpKit\WebConsole\Lib\Debug;

class LogCentral implements LogCentralInterface
{

	protected $formatters;
	protected $handlers;
	protected $loggers;
	protected $processors;
	protected $ready = false;
	private $formattersToHandlers = [];
	private $handlersToProcessors = [];
	private $loggersToHandlers = [];
	private $loggersToProcessors = [];

	public function __construct()
	{
		$this->formatters = new Formatters;
		$this->handlers = new Handlers;
		$this->loggers = new Loggers;
		$this->processors = new Processors;
	}

	function assignFormattersToHandlers(array $map)
	{
		if ($this->ready)
			foreach ($map as $f => $hl)
			{
				$formatter = $this->formatters->get($f);
				$handlers = is_string($hl) ? [$hl] : $hl;
				foreach ($handlers as $h)
				{
					$handler = $this->handlers->get($h);
					if ($handler instanceof FormattableHandlerInterface)
						$handler->setFormatter($formatter);
				}
			}
		else
			$this->formattersToHandlers[] = $map;
		return $this;
	}

	public function connectHandlersToProcessors(array $map)
	{
		if ($this->ready)
			foreach ($map as $h => $pl)
			{
				$handler = $this->handlers->get($h);
				$processors = is_string($pl) ? [$pl] : $pl;
				foreach ($processors as $p)
				{
					$processor = $this->processors->get($p);
					$handler->pushProcessor($processor);
				}
			}
		else
			$this->handlersToProcessors[] = $map;
		return $this;
	}

	function connectLoggersToHandlers(array $map)
	{
		if ($this->ready)
			foreach ($map as $l => $hl)
			{
				$logger = $this->loggers->get($l);
				$handlers = is_string($hl) ? [$hl] : $hl;
				foreach ($handlers as $h)
				{
					$handler = $this->handlers->get($h);
					$logger->pushHandler($handler);
				}
			}
		else
			$this->loggersToHandlers[] = $map;
		return $this;
	}

	function connectLoggersToProcessors(array $map)
	{
		if ($this->ready)
			foreach ($map as $l => $pl)
			{
				$logger = $this->loggers->get($l);
				$processors = is_string($pl) ? [$pl] : $pl;
				foreach ($processors as $p)
				{
					$processor = $this->processors->get($p);
					$logger->pushProcessor($processor);
				}
			}
		else
			$this->loggersToProcessors[] = $map;
		return $this;
	}

	function formatters()
	{
		return $this->formatters;
	}

	function handler($name)
	{
		return $this->handlers->has($name) ? $this->handlers->get($name) : new LogHandlerRef($this->handlers, $name);
	}

	function handlerGroup(...$names)
	{
		return new GroupHandler(map($names, function ($name)
			{
				return $this->handler($name);
			}));
	}

	function handlers()
	{
		return $this->handlers;
	}

	function loggers()
	{
		return $this->loggers;
	}

	function processors()
	{
		return $this->processors;
	}

	/**
	 * Post-confguration setup. This is called after all modules have registered stuff on the central.
	 */
	public function setup()
	{
		$this->ready = true;
		foreach ($this->formattersToHandlers as $r)
			$this->assignFormattersToHandlers($r);
		foreach ($this->handlersToProcessors as $r)
			$this->connectHandlersToProcessors($r);
		foreach ($this->loggersToHandlers as $r)
			$this->connectLoggersToHandlers($r);
		foreach ($this->loggersToProcessors as $r)
			$this->connectLoggersToProcessors($r);
	}

}
