<?php
namespace Electro\Logging\Lib;

use Electro\Interop\Logging\Handlers;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\LogRecord;

/**
 * A handler proxy referencing by name a handler that may not yet be registered when the proxy is instantiated.
 */
class LogHandlerRef implements HandlerInterface
{

	/** @var HandlerInterface */
	protected $handler = null;

	/** @var Handlers */
	private $handlers;

	/** @var string */
	private $name;

	/**
	 * RegisteredHandlerRef constructor.
	 *
	 * @param Handlers $handlers The handler registry.
	 * @param string   $name     The handler registration name.
	 */
	function __construct(Handlers $handlers, $name)
	{
		$this->handlers = $handlers;
		$this->name = $name;
	}

	function getFormatter()
	{
		return $this->handler()->getFormatter();
	}

	function handle(array $record): bool
	{
		return $this->handler()->handle($record);
	}

	function handleBatch(array $records): void
	{
		$this->handler()->handleBatch($records);
	}

	function isHandling(array $record): bool
	{
		return $this->handler()->isHandling($record);
	}

	function popProcessor()
	{
		return $this->handler()->popProcessor();
	}

	function pushProcessor($callback)
	{
		$this->handler()->pushProcessor($callback);
		return $this;
	}

	function setFormatter(FormatterInterface $formatter)
	{
		$this->handler()->setFormatter($formatter);
		return $this;
	}

	private function handler()
	{
		return $this->handler ?: $this->handlers->get($this->name);
	}

	public function close(): void
	{
		$this->handler()->close();
	}

}
